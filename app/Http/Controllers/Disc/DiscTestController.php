<?php

namespace App\Http\Controllers\Disc;

use App\Http\Controllers\Controller;
use App\Http\Requests\StartDiscTestRequest;
use App\Models\DiscAnswer;
use App\Models\DiscGraphConversion;
use App\Models\DiscProfile;
use App\Models\DiscQuestion;
use App\Models\DiscTest;
use App\Models\Merchant;
use App\Services\DiscSummaryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DiscTestController extends Controller
{
    private const SESSION_MERCHANT_KEY = 'disc.merchant_id';

    public function __construct(
        private DiscSummaryService $discSummaryService
    ) {}

    /**
     * Form Biodata
     */
    public function index(Request $request, ?Merchant $merchant = null): Response
    {
        if ($merchant !== null && ! $merchant->is_active) {
            abort(404);
        }

        $request->session()->put(self::SESSION_MERCHANT_KEY, $merchant?->id);

        return Inertia::render('DISC/Biodata', [
            'merchantName' => $merchant?->name,
        ]);
    }

    /**
     * Simpan biodata & lanjut ke instruksi
     */
    public function start(StartDiscTestRequest $request)
    {
        $merchantId = $request->session()->get(self::SESSION_MERCHANT_KEY);

        if ($merchantId !== null && ! Merchant::where('id', $merchantId)->where('is_active', true)->exists()) {
            $merchantId = null;
        }

        $request->session()->forget(self::SESSION_MERCHANT_KEY);

        $discTest = DiscTest::create([
            'participant_name' => $request->participant_name,
            'age' => $request->age,
            'gender' => $request->gender,
            'status' => 'draft',
            'merchant_id' => $merchantId,
        ]);

        return redirect()->route('disc.instruction', $discTest->id);
    }

    /**
     * Halaman Instruksi Tes
     */
    public function instruction(DiscTest $discTest): Response
    {
        return Inertia::render('DISC/Instruction', [
            'discTest' => $discTest,
        ]);
    }

    /**
     * Halaman Tes
     */
    public function test(DiscTest $discTest): Response
    {
        /*
         * Jika tes sudah selesai, jangan izinkan peserta
         * kembali mengerjakan.
         */
        if ($discTest->status === 'completed') {
            return Inertia::location(
                route('disc.result', $discTest)
            );
        }

        /*
         * Timer resmi dimulai saat halaman pengerjaan
         * pertama kali dibuka.
         */
        if (!$discTest->started_at) {
            $discTest->update([
                'started_at' => now(),
                'status' => 'in_progress',
            ]);

            $discTest->refresh();
        }

        $questions = DiscQuestion::query()
            ->where('is_active', true)
            ->orderBy('question_number')
            ->get();

        return Inertia::render('DISC/Test', [
            'discTest' => $discTest,
            'questions' => $questions,
        ]);
    }

    /**
     * Submit jawaban Tes Gaya Kerja.
     *
     * reason:
     * - submitted = diselesaikan manual oleh peserta
     * - timeout   = otomatis ketika waktu 30 menit habis
     */
    public function submit(Request $request, DiscTest $discTest)
    {
        /*
         * Idempotent:
         * bila request terkirim dua kali tetapi tes sebenarnya
         * sudah selesai, jangan proses ulang scoring.
         */
        if ($discTest->status === 'completed') {
            return redirect()->route('disc.result', $discTest);
        }

        $validated = $request->validate([
            'reason' => [
                'required',
                'string',
                'in:submitted,timeout',
            ],

            'answers' => [
                'present',
                'array',
                'max:24',
            ],

            'answers.*.most_choice' => [
                'nullable',
                'integer',
                'between:1,4',
            ],

            'answers.*.least_choice' => [
                'nullable',
                'integer',
                'between:1,4',
            ],
        ]);

        $reason = $validated['reason'];
        $answersPayload = $validated['answers'];

        /*
         * =====================================================
         * SUBMIT MANUAL
         * =====================================================
         *
         * Peserta hanya boleh submit manual jika seluruh
         * 24 soal lengkap.
         */
        if ($reason === 'submitted') {
            if (count($answersPayload) !== 24) {
                return back()->withErrors([
                    'answers' => 'Seluruh 24 soal harus diselesaikan sebelum tes disubmit.',
                ]);
            }

            foreach ($answersPayload as $answer) {
                $mostChoice = $answer['most_choice'] ?? null;
                $leastChoice = $answer['least_choice'] ?? null;

                if (
                    $mostChoice === null
                    || $leastChoice === null
                    || (int) $mostChoice === (int) $leastChoice
                ) {
                    return back()->withErrors([
                        'answers' => 'Setiap soal harus memiliki satu pilihan P dan satu pilihan K yang berbeda.',
                    ]);
                }
            }
        }

        /*
         * =====================================================
         * TIMEOUT
         * =====================================================
         *
         * Jangan percaya timer JavaScript saja.
         *
         * Backend memastikan bahwa waktu resmi 30 menit
         * memang sudah habis berdasarkan started_at.
         */
        if ($reason === 'timeout') {
            if (!$discTest->started_at) {
                abort(409, 'Waktu pengerjaan tes belum dimulai.');
            }

            $startedAt = Carbon::parse($discTest->started_at);
            $deadline = $startedAt->copy()->addMinutes(30);

            if (now()->addSeconds(15)->lt($deadline)) {
                abort(409, 'Batas waktu tes belum habis.');
            }
        }

        DB::transaction(function () use (
            $answersPayload,
            $discTest
        ) {
            /*
             * Lock row test supaya dua request finalization
             * tidak memproses data yang sama secara bersamaan.
             */
            $lockedTest = DiscTest::query()
                ->whereKey($discTest->id)
                ->lockForUpdate()
                ->firstOrFail();

            /*
             * Bisa saja request pertama sudah selesai tepat sebelum
             * request kedua mendapatkan lock.
             */
            if ($lockedTest->status === 'completed') {
                return;
            }

            /*
             * Bersihkan jawaban lama jika sebelumnya ada.
             */
            $lockedTest->answers()->delete();

            /*
             * =================================================
             * SIMPAN JAWABAN VALID
             * =================================================
             *
             * Pada timeout:
             * - soal lengkap P + K disimpan
             * - soal kosong dilewati
             * - soal setengah terisi dilewati
             */
            foreach ($answersPayload as $questionId => $answer) {
                $mostChoice = isset($answer['most_choice'])
                    ? (int) $answer['most_choice']
                    : null;

                $leastChoice = isset($answer['least_choice'])
                    ? (int) $answer['least_choice']
                    : null;

                if (
                    !in_array($mostChoice, [1, 2, 3, 4], true)
                    || !in_array($leastChoice, [1, 2, 3, 4], true)
                    || $mostChoice === $leastChoice
                ) {
                    continue;
                }

                $question = DiscQuestion::query()
                    ->whereKey($questionId)
                    ->where('is_active', true)
                    ->first();

                /*
                 * Jangan proses ID soal yang tidak valid.
                 */
                if (!$question) {
                    continue;
                }

                DiscAnswer::create([
                    'disc_test_id' => $lockedTest->id,
                    'disc_question_id' => $question->id,

                    'most_choice' => $mostChoice,
                    'least_choice' => $leastChoice,

                    'most_dimension' => $question->{'mapping_'.$mostChoice},
                    'least_dimension' => $question->{'mapping_'.$leastChoice},
                ]);
            }

            $answers = $lockedTest
                ->answers()
                ->get();

            /*
             * =================================================
             * RAW SCORE
             * =================================================
             */
            $most = [
                'D' => 0,
                'I' => 0,
                'S' => 0,
                'C' => 0,
            ];

            $least = [
                'D' => 0,
                'I' => 0,
                'S' => 0,
                'C' => 0,
            ];

            foreach ($answers as $answer) {
                if (array_key_exists($answer->most_dimension, $most)) {
                    $most[$answer->most_dimension]++;
                }

                if (array_key_exists($answer->least_dimension, $least)) {
                    $least[$answer->least_dimension]++;
                }
            }

            /*
             * =================================================
             * CHANGE SCORE
             * =================================================
             */
            $change = [
                'D' => $most['D'] - $least['D'],
                'I' => $most['I'] - $least['I'],
                'S' => $most['S'] - $least['S'],
                'C' => $most['C'] - $least['C'],
            ];

            /*
             * =================================================
             * GRAPH SCORE (Graph I: Most, Graph II: Least,
             * Graph III: Change) — dikonversi per dimensi dari
             * norma-disc.xlsx via DiscGraphConversionSeeder.
             * =================================================
             */
            $mostGraph = $this->convertToGraphScores('most', $most);
            $leastGraph = $this->convertToGraphScores('least', $least);
            $graph = $this->convertToGraphScores('change', $change);

            /*
             * =================================================
             * PRIMARY + SECONDARY
             * =================================================
             */
            $graphScores = [
                'D' => (float) $graph['D'],
                'I' => (float) $graph['I'],
                'S' => (float) $graph['S'],
                'C' => (float) $graph['C'],
            ];

            arsort($graphScores);

            $types = array_keys($graphScores);
            $values = array_values($graphScores);

            $primary = $types[0];
            $secondary = $types[1];

            $primaryScore = $values[0];
            $secondaryScore = $values[1];

            /*
             * Jika dua skor teratas dekat <= 5 poin:
             * gunakan kombinasi, misalnya SD.
             *
             * Kalau selisih > 5:
             * gunakan tipe utama saja, misalnya S.
             */
            if (abs($primaryScore - $secondaryScore) <= 5) {
                $discType = $primary.$secondary;
            } else {
                $discType = $primary;
            }

            $profile = DiscProfile::query()
                ->where('code', $discType)
                ->first();

            /*
             * =================================================
             * FINALIZE
             * =================================================
             */
            $lockedTest->update([
                'finished_at' => now(),
                'status' => 'completed',

                'disc_profile_id' => $profile?->id,

                /*
                 * Raw Score
                 */
                'most_d' => $most['D'],
                'most_i' => $most['I'],
                'most_s' => $most['S'],
                'most_c' => $most['C'],

                'least_d' => $least['D'],
                'least_i' => $least['I'],
                'least_s' => $least['S'],
                'least_c' => $least['C'],

                /*
                 * Change Score
                 */
                'change_d' => $change['D'],
                'change_i' => $change['I'],
                'change_s' => $change['S'],
                'change_c' => $change['C'],

                /*
                 * Graph Score (Graph III: Change)
                 */
                'graph_d' => $graph['D'],
                'graph_i' => $graph['I'],
                'graph_s' => $graph['S'],
                'graph_c' => $graph['C'],

                /*
                 * Graph Score (Graph I: Most)
                 */
                'most_graph_d' => $mostGraph['D'],
                'most_graph_i' => $mostGraph['I'],
                'most_graph_s' => $mostGraph['S'],
                'most_graph_c' => $mostGraph['C'],

                /*
                 * Graph Score (Graph II: Least)
                 */
                'least_graph_d' => $leastGraph['D'],
                'least_graph_i' => $leastGraph['I'],
                'least_graph_s' => $leastGraph['S'],
                'least_graph_c' => $leastGraph['C'],

                /*
                 * Profile
                 */
                'primary_type' => $primary,
                'secondary_type' => $secondary,
                'disc_type' => $discType,
            ]);
        });

        return redirect()->route('disc.result', $discTest);
    }

    /**
     * Konversi raw score per dimensi (D/I/S/C) menjadi nilai grafik 0-100
     * memakai tabel norma yang sesuai ($graphType: 'most', 'least', atau
     * 'change'). Fallback ke 50 (netral) bila kombinasi raw score/dimensi
     * tidak ada pada tabel norma — idealnya tabel DiscGraphConversion
     * memiliki seluruh range yang dibutuhkan.
     *
     * @param  array<string,int>  $rawScores
     * @return array<string,int>
     */
    private function convertToGraphScores(string $graphType, array $rawScores): array
    {
        $graph = [];

        foreach ($rawScores as $dimension => $rawScore) {
            $graph[$dimension] = DiscGraphConversion::query()
                ->where('graph_type', $graphType)
                ->where('dimension', $dimension)
                ->where('raw_score', $rawScore)
                ->value('graph_score') ?? 50;
        }

        return $graph;
    }

    /**
     * Halaman hasil
     */
    public function result(DiscTest $discTest)
    {
        $discTest->load([
            'profile',
            'answers.question.interpretations',
        ]);

        $personalSummary = $this->discSummaryService->generate(
            $discTest
        );

        return Inertia::render('DISC/Result', [
            'discTest' => $discTest,
            'profile' => $discTest->profile,
            'personalSummary' => $personalSummary,
        ]);
    }
}