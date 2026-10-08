<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\CompetencyTest;
use App\Models\DiscTest;
use App\Models\Ist\IstTest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CandidateDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $discTest = DiscTest::query()->where('user_id', $user->id)->latest('id')->first();
        $competencyTest = CompetencyTest::query()->where('user_id', $user->id)->latest('id')->first();
        $istTest = IstTest::query()->where('user_id', $user->id)->latest('id')->first();

        $discStatus = $this->statusFor($discTest?->status);
        $competencyStatus = $this->statusFor($competencyTest?->status);
        $istStatus = $this->statusFor($istTest?->status);

        $allCompleted = $discStatus === 'completed'
            && $competencyStatus === 'completed'
            && $istStatus === 'completed';

        return Inertia::render('Candidate/Dashboard', [
            'position' => $user->position?->only(['id', 'name']),
            'allCompleted' => $allCompleted,
            'tests' => [
                'disc' => [
                    'status' => $discStatus,
                    'startUrl' => route('assessment.start'),
                    'resumeUrl' => $discTest ? route('disc.test', $discTest) : null,
                ],
                'competency' => [
                    'status' => $competencyStatus,
                    'startUrl' => route('assessment.start'),
                    'resumeUrl' => $competencyTest ? route('competency.test', $competencyTest) : null,
                ],
                'ist' => [
                    'status' => $istStatus,
                    'startUrl' => route('assessment.start'),
                    'resumeUrl' => $istTest ? route('ist.resume', $istTest) : null,
                ],
            ],
        ]);
    }

    private function statusFor(?string $status): string
    {
        if ($status === null) {
            return 'not_started';
        }

        return $status === 'completed' ? 'completed' : 'in_progress';
    }
}
