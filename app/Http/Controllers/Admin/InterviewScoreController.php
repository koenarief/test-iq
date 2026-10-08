<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CandidateAssessment;
use App\Services\Scoring\FinalScoringService;
use App\Services\Scoring\InterviewScoringService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class InterviewScoreController extends Controller
{
    public function __construct(
        private InterviewScoringService $interviewScoringService,
        private FinalScoringService $finalScoringService,
    ) {}

    public function edit(CandidateAssessment $candidateAssessment): Response
    {
        abort_unless($candidateAssessment->gate_passed, 403, 'Kandidat belum lolos Assessment Gate.');

        $candidateAssessment->load('candidate:id,name,email');

        return Inertia::render('Admin/InterviewScores/Edit', [
            'candidateAssessment' => [
                'id' => $candidateAssessment->id,
                'candidate' => $candidateAssessment->candidate->only(['id', 'name', 'email']),
                'interview_communication' => $candidateAssessment->interview_communication,
                'interview_motivation' => $candidateAssessment->interview_motivation,
                'interview_problem_solving' => $candidateAssessment->interview_problem_solving,
                'interview_job_knowledge' => $candidateAssessment->interview_job_knowledge,
                'interview_attitude' => $candidateAssessment->interview_attitude,
                'interview_work_alignment' => $candidateAssessment->interview_work_alignment,
                'interview_notes' => $candidateAssessment->interview_notes,
            ],
            'weights' => InterviewScoringService::WEIGHTS,
        ]);
    }

    public function update(Request $request, CandidateAssessment $candidateAssessment): RedirectResponse
    {
        abort_unless($candidateAssessment->gate_passed, 403, 'Kandidat belum lolos Assessment Gate.');

        $validated = $request->validate([
            'interview_communication' => ['required', 'numeric', 'min:0', 'max:100'],
            'interview_motivation' => ['required', 'numeric', 'min:0', 'max:100'],
            'interview_problem_solving' => ['required', 'numeric', 'min:0', 'max:100'],
            'interview_job_knowledge' => ['required', 'numeric', 'min:0', 'max:100'],
            'interview_attitude' => ['required', 'numeric', 'min:0', 'max:100'],
            'interview_work_alignment' => ['required', 'numeric', 'min:0', 'max:100'],
            'interview_notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($request, $candidateAssessment, $validated) {
            $interviewScore = $this->interviewScoringService->score([
                'communication' => $validated['interview_communication'],
                'motivation' => $validated['interview_motivation'],
                'problem_solving' => $validated['interview_problem_solving'],
                'job_knowledge' => $validated['interview_job_knowledge'],
                'attitude' => $validated['interview_attitude'],
                'work_alignment' => $validated['interview_work_alignment'],
            ]);

            $candidateAssessment->fill([
                'interview_communication' => $validated['interview_communication'],
                'interview_motivation' => $validated['interview_motivation'],
                'interview_problem_solving' => $validated['interview_problem_solving'],
                'interview_job_knowledge' => $validated['interview_job_knowledge'],
                'interview_attitude' => $validated['interview_attitude'],
                'interview_work_alignment' => $validated['interview_work_alignment'],
                'interview_notes' => $validated['interview_notes'] ?? null,
                'interview_score' => $interviewScore,
                'interviewed_by' => $request->user()->id,
                'interview_scored_at' => now(),
            ]);

            if ($candidateAssessment->job_fit_score !== null) {
                $final = $this->finalScoringService->score($candidateAssessment);
                $candidateAssessment->final_score = $final['final_score'];
                $candidateAssessment->decision = $final['decision'];
                $candidateAssessment->final_scored_at = now();
            }

            $candidateAssessment->save();
        });

        return redirect()
            ->route('admin.assessment-recaps.show', $candidateAssessment)
            ->with('success', 'Skor interview berhasil disimpan.');
    }
}
