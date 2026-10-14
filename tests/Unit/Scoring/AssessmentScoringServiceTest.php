<?php

namespace Tests\Unit\Scoring;

use App\Models\CompetencyTest;
use App\Models\DiscTest;
use App\Services\Scoring\AssessmentScoringService;
use PHPUnit\Framework\TestCase;

class AssessmentScoringServiceTest extends TestCase
{
    public function test_disc_score_is_the_dominant_dimension_graph_value(): void
    {
        $test = new DiscTest([
            'graph_d' => 40,
            'graph_i' => 72,
            'graph_s' => 55,
            'graph_c' => 30,
        ]);

        $this->assertSame(72.0, AssessmentScoringService::scoreDisc($test));
    }

    public function test_competency_score_rescales_zero_to_five_points_to_zero_to_100(): void
    {
        $test = new CompetencyTest(['total_score' => 4]);

        $this->assertSame(80.0, AssessmentScoringService::scoreCompetency($test));
    }

    public function test_assessment_indicator_minimums_match_konsep_hrd(): void
    {
        $this->assertSame(60.0, AssessmentScoringService::DISC_INDICATOR_MIN);
        $this->assertSame(60.0, AssessmentScoringService::IST_INDICATOR_MIN);
        $this->assertSame(75.0, AssessmentScoringService::ASSESSMENT_INDICATOR_MIN);
    }

    public function test_assessment_weights_match_konsep_hrd(): void
    {
        $this->assertSame(1.0, round(
            AssessmentScoringService::DISC_WEIGHT
            + AssessmentScoringService::IST_WEIGHT,
            2,
        ));
        $this->assertSame(0.50, AssessmentScoringService::DISC_WEIGHT);
        $this->assertSame(0.50, AssessmentScoringService::IST_WEIGHT);
    }
}
