<?php

namespace Tests\Unit\Scoring;

use App\Services\Scoring\InterviewHrdScoringService;
use PHPUnit\Framework\TestCase;

class InterviewHrdScoringServiceTest extends TestCase
{
    public function test_weights_match_konsep_hrd_section_5(): void
    {
        $this->assertSame(1.0, array_sum(InterviewHrdScoringService::WEIGHTS));
        $this->assertSame(0.20, InterviewHrdScoringService::WEIGHTS['attitude_ethics']);
        $this->assertSame(0.15, InterviewHrdScoringService::WEIGHTS['communication']);
        $this->assertSame(0.15, InterviewHrdScoringService::WEIGHTS['motivation_goals']);
        $this->assertSame(0.20, InterviewHrdScoringService::WEIGHTS['discipline_responsibility']);
        $this->assertSame(0.15, InterviewHrdScoringService::WEIGHTS['commitment_readiness']);
        $this->assertSame(0.15, InterviewHrdScoringService::WEIGHTS['adaptability_teamwork']);
    }

    public function test_score_is_weighted_sum_of_aspects(): void
    {
        $service = new InterviewHrdScoringService();

        $score = $service->score([
            'attitude_ethics' => 80,
            'communication' => 80,
            'motivation_goals' => 80,
            'discipline_responsibility' => 80,
            'commitment_readiness' => 80,
            'adaptability_teamwork' => 80,
        ]);

        $this->assertSame(80.0, $score);
    }

    public function test_missing_aspect_defaults_to_zero(): void
    {
        $service = new InterviewHrdScoringService();

        $score = $service->score(['attitude_ethics' => 100]);

        $this->assertSame(20.0, $score);
    }
}
