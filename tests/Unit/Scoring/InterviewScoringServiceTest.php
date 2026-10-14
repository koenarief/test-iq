<?php

namespace Tests\Unit\Scoring;

use App\Services\Scoring\InterviewScoringService;
use PHPUnit\Framework\TestCase;

class InterviewScoringServiceTest extends TestCase
{
    public function test_weights_match_konsep_hrd(): void
    {
        $this->assertSame(0.40, InterviewScoringService::HRD_WEIGHT);
        $this->assertSame(0.60, InterviewScoringService::USER_WEIGHT);
    }

    public function test_combine_is_weighted_sum_of_hrd_and_user(): void
    {
        $service = new InterviewScoringService();

        // 80*0.40 + 90*0.60 = 32 + 54 = 86
        $this->assertSame(86.0, $service->combine(80, 90));
    }
}
