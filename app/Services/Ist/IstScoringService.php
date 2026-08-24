<?php
namespace App\Services\Ist;

use App\Models\IstTestSession;
use App\Models\IstAnswerKey;
use App\Models\IstNormSubtest;
use App\Models\IstNormTotal;
use App\Models\IstUserResponse;
use Illuminate\Support\Facades\DB;

class IstScoringService
{
    /**
     * Hitung otomatis seluruh skor dan simpan ke database
     */
    public function processScoring(IstTestSession $session, array $responses): IstTestSession
    {
        return DB::transaction(function () use ($session, $responses) {
            $subtests = ['SE', 'WA', 'AN', 'GE', 'RA', 'ZR', 'FA', 'WU', 'ME'];
            $rawScores = array_fill_keys($subtests, 0);

            // 1. Hitung Raw Score (RW) berdasarkan Kunci Jawaban
            foreach ($responses as $subtest => $answers) {
                $keys = IstAnswerKey::where('subtest', $subtest)->get()->keyBy('question_number');

                foreach ($answers as $qNum => $userAns) {
                    $score = 0;
                    if (isset($keys[$qNum])) {
                        $key = $keys[$qNum];
                        
                        // Khusus GE: Menggunakan logika penilain kata kunci / skala 0-2
                        if ($subtest === 'GE') {
                            $score = $this->scoreGeQuestion($userAns, $key->correct_answer);
                        // } else {
                        //     // Subtes Pilihan Ganda & Angka Biasa
                        //     if (strtolower(trim($userAns)) === strtolower(trim($key->correct_answer))) {
                        //         $score = $key->score_weight;
                        //     }
                        }

			// koreksi skor non GE??
			// Penanganan Logika =IF(C18=B73, 1, 0) pada Laravel Engine
			if ($subtest !== 'GE') {
			    // Normalisasi teks (mengabaikan spasi berlebih & kapitalisasi)
			    $cleanUserAns = strtolower(trim($userAns));
			    $cleanKeyAns  = strtolower(trim($key->correct_answer));

			    if ($cleanUserAns === $cleanKeyAns) {
				$score = $key->score_weight; // Bernilai 1 (sesuai logika IF Excel)
			    } else {
				$score = 0;
			    }
			}

                    }

                    // Simpan response per item
                    IstUserResponse::create([
                        'test_session_id' => $session->id,
                        'subtest' => $subtest,
                        'question_number' => $qNum,
                        'user_answer' => $userAns,
                        'earned_score' => $score
                    ]);

                    $rawScores[$subtest] += $score;
                }
            }

            // 2. Konversi Raw Score GE ke RW Standar (Menggunakan Tabel Konversi GE jika diperlukan)
            // (Logika konversi khusus GE dapat disesuaikan di sini)

            // 3. Simpan Raw Scores (RW) ke Session
            foreach ($rawScores as $subtest => $rw) {
                $col = 'rw_' . strtolower($subtest);
                $session->$col = $rw;
            }
            $session->total_rw = array_sum($rawScores);

            // 4. Konversi Raw Score (RW) -> Standard Score (SW) berdasarkan Usia Peserta
            $standardScores = [];
            $age = $session->age;

            foreach ($rawScores as $subtest => $rw) {
                $swNorm = IstNormSubtest::where('subtest', $subtest)
                    ->where('min_age', '<=', $age)
                    ->where('max_age', '>=', $age)
                    ->where('raw_score', $rw)
                    ->first();

                $sw = $swNorm ? $swNorm->standard_score : 100; // Default fallback SW
                $standardScores[$subtest] = $sw;
                
                $swCol = 'sw_' . strtolower($subtest);
                $session->$swCol = $sw;
            }

            $totalSw = array_sum($standardScores);
            $session->total_sw = $totalSw;

            // 5. Lookup TOTAL SW -> IQ & Kategori Psikotes
            $normTotal = IstNormTotal::where('min_age', '<=', $age)
                ->where('max_age', '>=', $age)
                ->where('total_sw', $totalSw)
                ->first();

            if ($normTotal) {
                $session->iq_score = $normTotal->iq_score;
                $session->iq_category = $normTotal->iq_category;
            }

            // 6. Analisis Profil Dominasi Inteligensi (M-Dominan vs W-Dominan)
            $session->dominance_profile = $this->calculateDominanceProfile($standardScores);

            $session->save();
            return $session;
        });
    }

    /**
     * Hitung Penilaian Kata Kunci Subtes GE (0, 1, 2)
     */
    private function scoreGeQuestion(string $userAns, string $keyConfig): int
    {
        // Parse konfigurasi JSON kunci jika tersimpan sebagai pola kata kunci
        $config = json_decode($keyConfig, true);
        if (!$config) {
            return strtolower(trim($userAns)) === strtolower(trim($keyConfig)) ? 2 : 0;
        }

        $userAnsClean = strtolower(trim($userAns));

        // Cek kata kunci bernilai 2
        if (isset($config['score_2'])) {
            foreach ($config['score_2'] as $keyword) {
                if (str_contains($userAnsClean, strtolower($keyword))) {
                    return 2;
                }
            }
        }

        // Cek kata kunci bernilai 1
        if (isset($config['score_1'])) {
            foreach ($config['score_1'] as $keyword) {
                if (str_contains($userAnsClean, strtolower($keyword))) {
                    return 1;
                }
            }
        }

        return 0;
    }

    /**
     * Menghitung Profil Dominasi Verbal (W) vs Visual/Sparsial (M)
     */
    private function calculateDominanceProfile(array $sw): string
    {
        $verbal = $sw['SE'] + $sw['WA'] + $sw['AN'] + $sw['GE'];
        $spatial = $sw['FA'] + $sw['WU'] + $sw['ZR'] + $sw['RA'];

        $diff = $verbal - $spatial;

        if ($diff >= 10) {
            return 'W-Dominant (Verbal High)';
        } elseif ($diff <= -10) {
            return 'M-Dominant (Spatial High)';
        }

        return 'Seimbang (Balanced)';
    }



    public function calculateSessionScore(int $sessionId): array
    {
        $session = IstTestSession::with('responses')->findOrFail($sessionId);
        $age = $session->age;

        // 1. Ambil seluruh kunci jawaban
        $answerKeys = IstAnswerKey::all()->keyBy(function ($item) {
            return $item->subtest . '_' . $item->question_number;
        });

        $subtestRw = [
            'SE' => 0, 'WA' => 0, 'AN' => 0, 'GE' => 0,
            'RA' => 0, 'ZR' => 0, 'FA' => 0, 'WU' => 0, 'ME' => 0
        ];

        // 2. Kalkulasi Raw Score (RW)
        foreach ($session->responses as $response) {
            $key = $response->subtest . '_' . $response->question_number;
            if (!isset($answerKeys[$key])) continue;

            $correctKey = $answerKeys[$key];
            $earnedScore = 0;

            if ($response->subtest === 'GE') {
                // Grading kata kunci GE (0, 1, atau 2)
                $keywords = json_decode($correctKey->correct_answer, true);
                $userAns = strtolower(trim($response->user_answer));

                if (in_array($userAns, $keywords['score_2'] ?? [])) {
                    $earnedScore = 2;
                } elseif (in_array($userAns, $keywords['score_1'] ?? [])) {
                    $earnedScore = 1;
                }
            } else {
                // Grading Pilihan Ganda & Angka
                if (strtolower(trim($response->user_answer)) === strtolower(trim($correctKey->correct_answer))) {
                    $earnedScore = 1;
                }
            }

            // Simpan skor per butir soal
            $response->update(['earned_score' => $earnedScore]);
            $subtestRw[$response->subtest] += $earnedScore;
        }

        // 3. Lookup Standard Score (SW) per Subtes berdasarkan Usia
        $subtestSw = [];
        $subtestCategory = [];
        $totalSw = 0;

        foreach ($subtestRw as $subtest => $rw) {
            $norm = IstNormSubtest::lookup($age, $subtest, $rw)->first();
            $sw = $norm ? $norm->standard_score : 100; // default 100 jika tidak ditemukan
            
            $subtestSw[$subtest] = $sw;
            $subtestCategory[$subtest] = $this->getSubtestCategory($sw);
            $totalSw += $sw;
        }

        // 4. Lookup Gesamt IQ Score & Kategori berdasarkan Total SW
        $normTotal = IstNormTotal::lookup($age, $totalSw)->first();
        $iqScore = $normTotal ? $normTotal->iq_score : 100;
        $iqCategory = $normTotal ? $normTotal->iq_category : 'Average';

        // 5. Analisis Dominasi Berpikir
        $verbalSw = $subtestSw['SE'] + $subtestSw['WA'] + $subtestSw['AN'] + $subtestSw['GE'];
        $spatialSw = $subtestSw['FA'] + $subtestSw['WU'] + $subtestSw['ME'];
        $dominance = $this->calculateDominance($verbalSw, $spatialSw);

        // 6. Simpan Hasil Akhir ke Session
        $session->update([
            'total_sw' => $totalSw,
            'iq_score' => $iqScore,
            'iq_category' => $iqCategory,
            'dominance_type' => $dominance,
            'subtest_scores' => json_encode([
                'rw' => $subtestRw,
                'sw' => $subtestSw,
                'categories' => $subtestCategory
            ])
        ]);

        return [
            'session_id' => $sessionId,
            'subtest_rw' => $subtestRw,
            'subtest_sw' => $subtestSw,
            'total_sw' => $totalSw,
            'iq_score' => $iqScore,
            'iq_category' => $iqCategory,
            'dominance' => $dominance
        ];
    }

    private function getSubtestCategory(int $sw): string
    {
        if ($sw > 118) return 'Sangat Tinggi';
        if ($sw >= 109) return 'Tinggi';
        if ($sw >= 91) return 'Rata-Rata';
        if ($sw >= 82) return 'Rendah';
        return 'Sangat Rendah';
    }

    private function calculateDominance(int $verbalSw, int $spatialSw): string
    {
        $diff = $verbalSw - $spatialSw;
        if ($diff > 15) return 'Dominan Verbal / Konseptual';
        if ($diff < -15) return 'Dominan Spasial / Praktis';
        return 'Seimbang (Balanced)';
    }
}