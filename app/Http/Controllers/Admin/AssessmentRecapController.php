<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CandidateAssessment;
use App\Services\Scoring\AssessmentScoringService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AssessmentRecapController extends Controller
{
    public function __construct(
        private AssessmentScoringService $assessmentScoringService,
    ) {}

    public function index(Request $request): Response
    {
        $search = $request->string('search')->value() ?: null;
        $decision = $request->string('decision')->value() ?: null;

        $assessments = CandidateAssessment::query()
            ->with(['candidate:id,name,email', 'position:id,name'])
            ->when($search, fn ($query) => $query->whereHas(
                'candidate',
                fn ($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%'),
            ))
            ->when($decision, fn ($query) => $query->where('decision', $decision))
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/AssessmentRecaps/Index', [
            'assessments' => $assessments,
            'filters' => ['search' => $search, 'decision' => $decision],
        ]);
    }

    public function show(CandidateAssessment $candidateAssessment): Response
    {
        $this->assessmentScoringService->recomputeForCandidate($candidateAssessment->candidate);
        $candidateAssessment->refresh();

        $candidateAssessment->load([
            'candidate:id,name,email',
            'position.competencyCategoryWeights.category',
            'discTest',
            'competencyTest',
            'istTest',
            'interviewer:id,name',
        ]);

        return Inertia::render('Admin/AssessmentRecaps/Show', [
            'assessment' => $candidateAssessment,
        ]);
    }
}
