<?php

namespace Tests\Unit\Scoring;

use App\Services\Scoring\InterviewScoringService;
use PHPUnit\Framework\TestCase;

class InterviewScoringServiceTest extends TestCase
{
    public function test_weights_match_score_md_example(): void
    {
        $this->assertSame(1.0, array_sum(InterviewScoringService::WEIGHTS));
        $this->assertSame(0.20, InterviewScoringService::WEIGHTS['communication']);
        $this->assertSame(0.15, InterviewScoringService::WEIGHTS['motivation']);
        $this->assertSame(0.20, InterviewScoringService::WEIGHTS['problem_solving']);
        $this->assertSame(0.20, InterviewScoringService::WEIGHTS['job_knowledge']);
        $this->assertSame(0.15, InterviewScoringService::WEIGHTS['attitude']);
        $this->assertSame(0.10, InterviewScoringService::WEIGHTS['work_alignment']);
    }

    public function test_score_is_weighted_sum_of_aspects(): void
    {
        $service = new InterviewScoringService();

        $score = $service->score([
            'communication' => 80,
            'motivation' => 80,
            'problem_solving' => 80,
            'job_knowledge' => 80,
            'attitude' => 80,
            'work_alignment' => 80,
        ]);

        $this->assertSame(80.0, $score);
    }

    public function test_missing_aspect_defaults_to_zero(): void
    {
        $service = new InterviewScoringService();

        $score = $service->score([
            'communication' => 100,
        ]);

        $this->assertSame(20.0, $score);
    }
}
