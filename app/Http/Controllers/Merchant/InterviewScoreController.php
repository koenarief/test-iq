<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Admin\InterviewScoreController as AdminInterviewScoreController;
use App\Models\CandidateAssessment;
use App\Models\CompetencyTest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class InterviewScoreController extends AdminInterviewScoreController
{
    public function editHrdFor(Request $request, CompetencyTest $competencyTest): Response
    {
        $assessment = $this->resolve($request, $competencyTest);

        return $this->renderHrd($assessment, $this->extra($competencyTest, 'hrd'));
    }

    public function updateHrdFor(Request $request, CompetencyTest $competencyTest): RedirectResponse
    {
        $this->saveHrd($request, $this->resolve($request, $competencyTest));

        return redirect()
            ->route('merchant.competency-results.show', $competencyTest)
            ->with('success', 'Skor Interview HRD berhasil disimpan.');
    }

    public function editUserFor(Request $request, CompetencyTest $competencyTest): Response
    {
        $assessment = $this->resolve($request, $competencyTest);

        return $this->renderUser($assessment, $this->extra($competencyTest, 'user'));
    }

    public function updateUserFor(Request $request, CompetencyTest $competencyTest): RedirectResponse
    {
        $this->saveUser($request, $this->resolve($request, $competencyTest));

        return redirect()
            ->route('merchant.competency-results.show', $competencyTest)
            ->with('success', 'Skor Interview User berhasil disimpan.');
    }

    private function resolve(Request $request, CompetencyTest $competencyTest): CandidateAssessment
    {
        abort_unless($competencyTest->merchant_id === $request->user()->merchant_id, 404);

        return CandidateAssessment::where('competency_test_id', $competencyTest->id)->firstOrFail();
    }

    private function extra(CompetencyTest $competencyTest, string $stage): array
    {
        return [
            'layout' => 'merchant',
            'urls' => [
                'update' => route("merchant.competency-results.interview-{$stage}.update", $competencyTest),
                'cancel' => route('merchant.competency-results.show', $competencyTest),
            ],
        ];
    }
}
