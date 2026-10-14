<?php

namespace Tests\Unit\Scoring;

use App\Models\CandidateAssessment;
use App\Services\Scoring\FinalScoringService;
use PHPUnit\Framework\TestCase;

class FinalScoringServiceTest extends TestCase
{
    private function assessment(float $assessmentScore, float $jobFitScore, float $interviewScore): CandidateAssessment
    {
        return new CandidateAssessment([
            'assessment_score' => $assessmentScore,
            'job_fit_score' => $jobFitScore,
            'interview_score' => $interviewScore,
        ]);
    }

    public function test_final_score_uses_konsep_hrd_weights(): void
    {
        $service = new FinalScoringService();

        $result = $service->score($this->assessment(80, 70, 90));

        // 80*0.35 + 70*0.35 + 90*0.30 = 28 + 24.5 + 27 = 79.5
        $this->assertSame(79.5, $result['final_score']);
    }

    public function test_decision_bands_match_score_md(): void
    {
        $service = new FinalScoringService();

        $this->assertSame(
            CandidateAssessment::DECISION_RECOMMENDED,
            $service->score($this->assessment(90, 90, 90))['decision'],
        );

        $this->assertSame(
            CandidateAssessment::DECISION_CONSIDER,
            $service->score($this->assessment(80, 80, 80))['decision'],
        );

        $this->assertSame(
            CandidateAssessment::DECISION_NOT_RECOMMENDED,
            $service->score($this->assessment(50, 50, 50))['decision'],
        );
    }

    public function test_decision_boundaries_are_inclusive_at_85_and_75(): void
    {
        $service = new FinalScoringService();

        // assessment*0.5 + jobfit*0.2 + interview*0.3 = 85 exactly
        $this->assertSame(
            CandidateAssessment::DECISION_RECOMMENDED,
            $service->score($this->assessment(85, 85, 85))['decision'],
        );

        $this->assertSame(
            CandidateAssessment::DECISION_CONSIDER,
            $service->score($this->assessment(75, 75, 75))['decision'],
        );

        $this->assertSame(
            CandidateAssessment::DECISION_NOT_RECOMMENDED,
            $service->score($this->assessment(74.99, 74.99, 74.99))['decision'],
        );
    }
}
