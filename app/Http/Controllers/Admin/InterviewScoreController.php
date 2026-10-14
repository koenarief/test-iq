<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CandidateAssessment;
use App\Services\Scoring\AssessmentScoringService;
use App\Services\Scoring\InterviewHrdScoringService;
use App\Services\Scoring\InterviewScoringService;
use App\Services\Scoring\InterviewUserScoringService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class InterviewScoreController extends Controller
{
    public function __construct(
        private InterviewHrdScoringService $interviewHrdScoringService,
        private InterviewUserScoringService $interviewUserScoringService,
        private InterviewScoringService $interviewScoringService,
        private AssessmentScoringService $assessmentScoringService,
    ) {}

    /**
     * "Lanjut Interview HRD" is always available — no score/gate ever
     * blocks this per konsep-hrd.pdf §10.B.
     */
    public function editHrd(CandidateAssessment $candidateAssessment): Response
    {
        $candidateAssessment->load('candidate:id,name,email');

        return Inertia::render('Admin/InterviewScores/HrdEdit', [
            'candidateAssessment' => [
                'id' => $candidateAssessment->id,
                'candidate' => $candidateAssessment->candidate->only(['id', 'name', 'email']),
                'interview_hrd_attitude_ethics' => $candidateAssessment->interview_hrd_attitude_ethics,
                'interview_hrd_communication' => $candidateAssessment->interview_hrd_communication,
                'interview_hrd_motivation_goals' => $candidateAssessment->interview_hrd_motivation_goals,
                'interview_hrd_discipline_responsibility' => $candidateAssessment->interview_hrd_discipline_responsibility,
                'interview_hrd_commitment_readiness' => $candidateAssessment->interview_hrd_commitment_readiness,
                'interview_hrd_adaptability_teamwork' => $candidateAssessment->interview_hrd_adaptability_teamwork,
                'interview_hrd_notes' => $candidateAssessment->interview_hrd_notes,
            ],
            'weights' => InterviewHrdScoringService::WEIGHTS,
        ]);
    }

    public function updateHrd(Request $request, CandidateAssessment $candidateAssessment): RedirectResponse
    {
        $validated = $request->validate([
            'interview_hrd_attitude_ethics' => ['required', 'numeric', 'min:1', 'max:100'],
            'interview_hrd_communication' => ['required', 'numeric', 'min:1', 'max:100'],
            'interview_hrd_motivation_goals' => ['required', 'numeric', 'min:1', 'max:100'],
            'interview_hrd_discipline_responsibility' => ['required', 'numeric', 'min:1', 'max:100'],
            'interview_hrd_commitment_readiness' => ['required', 'numeric', 'min:1', 'max:100'],
            'interview_hrd_adaptability_teamwork' => ['required', 'numeric', 'min:1', 'max:100'],
            'interview_hrd_notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($request, $candidateAssessment, $validated) {
            $hrdScore = $this->interviewHrdScoringService->score([
                'attitude_ethics' => $validated['interview_hrd_attitude_ethics'],
                'communication' => $validated['interview_hrd_communication'],
                'motivation_goals' => $validated['interview_hrd_motivation_goals'],
                'discipline_responsibility' => $validated['interview_hrd_discipline_responsibility'],
                'commitment_readiness' => $validated['interview_hrd_commitment_readiness'],
                'adaptability_teamwork' => $validated['interview_hrd_adaptability_teamwork'],
            ]);

            $candidateAssessment->fill([
                'interview_hrd_attitude_ethics' => $validated['interview_hrd_attitude_ethics'],
                'interview_hrd_communication' => $validated['interview_hrd_communication'],
                'interview_hrd_motivation_goals' => $validated['interview_hrd_motivation_goals'],
                'interview_hrd_discipline_responsibility' => $validated['interview_hrd_discipline_responsibility'],
                'interview_hrd_commitment_readiness' => $validated['interview_hrd_commitment_readiness'],
                'interview_hrd_adaptability_teamwork' => $validated['interview_hrd_adaptability_teamwork'],
                'interview_hrd_notes' => $validated['interview_hrd_notes'] ?? null,
                'interview_hrd_score' => $hrdScore,
                'interview_hrd_by' => $request->user()->id,
                'interview_hrd_scored_at' => now(),
            ]);

            // If User stage was already done (re-editing HRD), recombine.
            if ($candidateAssessment->interview_user_score !== null) {
                $candidateAssessment->interview_score = $this->interviewScoringService->combine(
                    (float) $hrdScore,
                    (float) $candidateAssessment->interview_user_score,
                );
                $candidateAssessment->interview_scored_at = now();
            }

            $candidateAssessment->save();

            $this->assessmentScoringService->recomputeFinalScore($candidateAssessment);
        });

        return redirect()
            ->route('admin.assessment-recaps.show', $candidateAssessment)
            ->with('success', 'Skor Interview HRD berhasil disimpan.');
    }

    /**
     * "Lanjut Interview User" only requires the HRD stage to exist first —
     * this is a data-sequencing requirement (the flow is HRD then User),
     * not a score threshold, so it never blocks on any score value.
     */
    public function editUser(CandidateAssessment $candidateAssessment): Response
    {
        abort_unless(
            $candidateAssessment->interview_hrd_scored_at !== null,
            403,
            'Interview HRD harus diselesaikan terlebih dahulu.'
        );

        $candidateAssessment->load('candidate:id,name,email');

        return Inertia::render('Admin/InterviewScores/UserEdit', [
            'candidateAssessment' => [
                'id' => $candidateAssessment->id,
                'candidate' => $candidateAssessment->candidate->only(['id', 'name', 'email']),
                'interview_user_task_understanding' => $candidateAssessment->interview_user_task_understanding,
                'interview_user_work_competence' => $candidateAssessment->interview_user_work_competence,
                'interview_user_problem_analysis' => $candidateAssessment->interview_user_problem_analysis,
                'interview_user_work_quality' => $candidateAssessment->interview_user_work_quality,
                'interview_user_initiative_independence' => $candidateAssessment->interview_user_initiative_independence,
                'interview_user_communication_teamwork' => $candidateAssessment->interview_user_communication_teamwork,
                'interview_user_notes' => $candidateAssessment->interview_user_notes,
            ],
            'weights' => InterviewUserScoringService::WEIGHTS,
        ]);
    }

    public function updateUser(Request $request, CandidateAssessment $candidateAssessment): RedirectResponse
    {
        abort_unless(
            $candidateAssessment->interview_hrd_scored_at !== null,
            403,
            'Interview HRD harus diselesaikan terlebih dahulu.'
        );

        $validated = $request->validate([
            'interview_user_task_understanding' => ['required', 'numeric', 'min:1', 'max:100'],
            'interview_user_work_competence' => ['required', 'numeric', 'min:1', 'max:100'],
            'interview_user_problem_analysis' => ['required', 'numeric', 'min:1', 'max:100'],
            'interview_user_work_quality' => ['required', 'numeric', 'min:1', 'max:100'],
            'interview_user_initiative_independence' => ['required', 'numeric', 'min:1', 'max:100'],
            'interview_user_communication_teamwork' => ['required', 'numeric', 'min:1', 'max:100'],
            'interview_user_notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($request, $candidateAssessment, $validated) {
            $userScore = $this->interviewUserScoringService->score([
                'task_understanding' => $validated['interview_user_task_understanding'],
                'work_competence' => $validated['interview_user_work_competence'],
                'problem_analysis' => $validated['interview_user_problem_analysis'],
                'work_quality' => $validated['interview_user_work_quality'],
                'initiative_independence' => $validated['interview_user_initiative_independence'],
                'communication_teamwork' => $validated['interview_user_communication_teamwork'],
            ]);

            $candidateAssessment->fill([
                'interview_user_task_understanding' => $validated['interview_user_task_understanding'],
                'interview_user_work_competence' => $validated['interview_user_work_competence'],
                'interview_user_problem_analysis' => $validated['interview_user_problem_analysis'],
                'interview_user_work_quality' => $validated['interview_user_work_quality'],
                'interview_user_initiative_independence' => $validated['interview_user_initiative_independence'],
                'interview_user_communication_teamwork' => $validated['interview_user_communication_teamwork'],
                'interview_user_notes' => $validated['interview_user_notes'] ?? null,
                'interview_user_score' => $userScore,
                'interview_user_by' => $request->user()->id,
                'interview_user_scored_at' => now(),
            ]);

            $candidateAssessment->interview_score = $this->interviewScoringService->combine(
                (float) $candidateAssessment->interview_hrd_score,
                (float) $userScore,
            );
            $candidateAssessment->interview_scored_at = now();

            $candidateAssessment->save();

            $this->assessmentScoringService->recomputeFinalScore($candidateAssessment);
        });

        return redirect()
            ->route('admin.assessment-recaps.show', $candidateAssessment)
            ->with('success', 'Skor Interview User berhasil disimpan.');
    }
}
