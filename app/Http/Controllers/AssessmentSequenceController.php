<?php

namespace App\Http\Controllers;

use App\Actions\Competency\SelectCompetencyQuestionsForTest;
use App\Actions\Ist\CreateIstParticipantSession;
use App\Http\Requests\BeginAssessmentRequest;
use App\Models\CompetencyTest;
use App\Models\DiscTest;
use App\Models\Ist\IstTest;
use App\Models\Instansi;
use App\Models\Merchant;
use App\Models\Position;
use App\Models\User;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AssessmentSequenceController extends Controller
{
    private const SESSION_MERCHANT_KEY = 'assessment.merchant_id';

    private const SESSION_BIODATA_KEY = 'assessment.biodata';

    public function __construct(
        private CreateIstParticipantSession $istParticipantSession,
        private SelectCompetencyQuestionsForTest $competencyQuestionSelector,
    ) {}

    /**
     * Form biodata tunggal sebelum rangkaian DISC -> IST -> Kompetensi.
     */
    public function start(Request $request, ?Merchant $merchant = null): Response
    {
        if ($merchant !== null && ! $merchant->is_active) {
            abort(404);
        }

        $request->session()->put(self::SESSION_MERCHANT_KEY, $merchant?->id);

        return Inertia::render('Assessment/Biodata', [
            'merchantName' => $merchant?->name,
            'instansiOptions' => Instansi::query()
                ->orderBy('nama')
                ->get(['id', 'nama']),
            'positions' => Position::query()
                ->active()
                ->orderBy('name')
                ->get(['id', 'name', 'department']),
        ]);
    }

    /**
     * Simpan biodata, buat identitas kandidat sekali pakai (tanpa form
     * register/login), lalu mulai tes pertama (DISC).
     */
    public function begin(BeginAssessmentRequest $request)
    {
        $merchantId = $request->session()->get(self::SESSION_MERCHANT_KEY);

        if ($merchantId !== null && ! Merchant::where('id', $merchantId)->where('is_active', true)->exists()) {
            $merchantId = null;
        }

        $candidate = User::create([
            'name' => $request->participant_name,
            'email' => 'candidate-'.Str::uuid().'@tes-iq.local',
            'password' => Hash::make(Str::random(40)),
            'role' => User::ROLE_CANDIDATE,
            'status' => User::STATUS_AKTIF,
            'instansi_id' => $request->instansi_id,
            'position_id' => $request->position_id,
        ]);

        Auth::login($candidate);
        $request->session()->regenerate();

        $request->session()->put(self::SESSION_BIODATA_KEY, [
            'age' => $request->age,
            'gender' => $request->gender,
        ]);

        $discTest = DiscTest::create([
            'user_id' => $candidate->id,
            'participant_name' => $candidate->name,
            'age' => $request->age,
            'gender' => $request->gender,
            'status' => 'draft',
            'merchant_id' => $merchantId,
        ]);

        return redirect()->route('disc.instruction', $discTest->id);
    }

    /**
     * Dipanggil dari halaman hasil DISC untuk lanjut otomatis ke IST.
     */
    public function continueToIst(Request $request)
    {
        $candidate = $request->user();

        $discTest = DiscTest::query()
            ->where('user_id', $candidate->id)
            ->latest('id')
            ->first();

        if (! $discTest) {
            return redirect()->route('assessment.start');
        }

        if ($discTest->status !== 'completed') {
            return redirect()->route('disc.instruction', $discTest->id);
        }

        $biodata = $request->session()->get(self::SESSION_BIODATA_KEY, []);

        try {
            $creation = $this->istParticipantSession->create(
                [
                    'participant_name' => $candidate->name,
                    'age' => $biodata['age'] ?? null,
                    'gender' => $biodata['gender'] ?? null,
                ],
                $discTest->merchant_id,
                $candidate->id,
            );
        } catch (DomainException $exception) {
            Log::warning('IST participant session creation was rejected.', [
                'exception' => $exception::class,
                'reason' => $exception->getMessage(),
            ]);

            abort(409, 'Asesmen belum tersedia.');
        }

        $rawToken = $creation->takeRawAccessToken();
        $request->session()->regenerate();
        $request->session()->put(
            "ist.access_tokens.{$creation->test->public_id}",
            $rawToken,
        );
        unset($rawToken);

        return redirect()->route('ist.resume', [
            'test' => $creation->test->public_id,
        ], 303);
    }

    /**
     * Dipanggil dari halaman hasil IST untuk lanjut otomatis ke Kompetensi.
     */
    public function continueToCompetency(Request $request)
    {
        $candidate = $request->user();

        $istTest = IstTest::query()
            ->where('user_id', $candidate->id)
            ->latest('id')
            ->first();

        if (! $istTest) {
            return redirect()->route('assessment.start');
        }

        if ($istTest->status !== IstTest::STATUS_COMPLETED) {
            return redirect()->route('ist.resume', ['test' => $istTest->public_id]);
        }

        $biodata = $request->session()->get(self::SESSION_BIODATA_KEY, []);
        $department = $candidate->position?->department;

        if (! $department) {
            abort(409, 'Jabatan/divisi kandidat tidak valid untuk tes kompetensi.');
        }

        $competencyTest = CompetencyTest::create([
            'user_id' => $candidate->id,
            'participant_name' => $candidate->name,
            'age' => $biodata['age'] ?? null,
            'gender' => $biodata['gender'] ?? null,
            'department' => $department,
            'status' => 'draft',
            'merchant_id' => $istTest->merchant_id,
        ]);

        $this->competencyQuestionSelector->handle($competencyTest);

        $request->session()->forget([self::SESSION_MERCHANT_KEY, self::SESSION_BIODATA_KEY]);

        return redirect()->route('competency.instruction', $competencyTest->id);
    }
}
