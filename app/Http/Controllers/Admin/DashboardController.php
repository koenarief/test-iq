<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CandidateAssessment;
use App\Services\Scoring\StrengthDevelopmentService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private StrengthDevelopmentService $strengthDevelopmentService,
    ) {}

    public function index(): Response
    {
        $jobFitResults = CandidateAssessment::query()
            ->whereNotNull('job_fit_score')
            ->with(['candidate:id,name,email', 'position:id,name'])
            ->orderByDesc('job_fit_scored_at')
            ->limit(10)
            ->get()
            ->map(fn (CandidateAssessment $assessment) => [
                'id' => $assessment->id,
                'candidate_name' => $assessment->candidate?->name,
                'candidate_email' => $assessment->candidate?->email,
                'position_name' => $assessment->position?->name,
                'job_fit_score' => (float) $assessment->job_fit_score,
                'job_fit_band' => $this->strengthDevelopmentService->band(
                    (float) $assessment->job_fit_score,
                ),
            ]);

        return Inertia::render('Admin/Dashboard', [
            'jobFitResults' => $jobFitResults,
        ]);
    }
}
