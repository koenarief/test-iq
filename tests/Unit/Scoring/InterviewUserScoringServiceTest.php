<?php

namespace Tests\Unit\Scoring;

use App\Services\Scoring\InterviewUserScoringService;
use PHPUnit\Framework\TestCase;

class InterviewUserScoringServiceTest extends TestCase
{
    public function test_weights_match_konsep_hrd_section_6(): void
    {
        $this->assertSame(1.0, array_sum(InterviewUserScoringService::WEIGHTS));
        $this->assertSame(0.20, InterviewUserScoringService::WEIGHTS['task_understanding']);
        $this->assertSame(0.25, InterviewUserScoringService::WEIGHTS['work_competence']);
        $this->assertSame(0.15, InterviewUserScoringService::WEIGHTS['problem_analysis']);
        $this->assertSame(0.15, InterviewUserScoringService::WEIGHTS['work_quality']);
        $this->assertSame(0.10, InterviewUserScoringService::WEIGHTS['initiative_independence']);
        $this->assertSame(0.15, InterviewUserScoringService::WEIGHTS['communication_teamwork']);
    }

    public function test_score_is_weighted_sum_of_aspects(): void
    {
        $service = new InterviewUserScoringService();

        $score = $service->score([
            'task_understanding' => 80,
            'work_competence' => 80,
            'problem_analysis' => 80,
            'work_quality' => 80,
            'initiative_independence' => 80,
            'communication_teamwork' => 80,
        ]);

        $this->assertSame(80.0, $score);
    }

    public function test_missing_aspect_defaults_to_zero(): void
    {
        $service = new InterviewUserScoringService();

        $score = $service->score(['work_competence' => 100]);

        $this->assertSame(25.0, $score);
    }
}
