<?php

namespace Tests\Unit\Scoring;

use App\Models\DiscTest;
use App\Services\Scoring\AssessmentScoringService;
use Tests\TestCase;

class DiscFitScoreTest extends TestCase
{
    public function test_every_department_disc_weights_sum_to_100(): void
    {
        foreach (config('competency.departments') as $key => $department) {
            $this->assertSame(100, array_sum($department['disc_weights']), $key);
        }
    }

    public function test_disc_score_uses_position_department_weights(): void
    {
        $test = new DiscTest([
            'graph_d' => 40,
            'graph_i' => 80,
            'graph_s' => 60,
            'graph_c' => 50,
        ]);

        // Sales: D30 I35 S15 C20 -> 12 + 28 + 9 + 10
        $this->assertSame(59.0, AssessmentScoringService::scoreDisc($test, 'sales'));

        // Finance: D20 I15 S25 C40 -> 8 + 12 + 15 + 20
        $this->assertSame(55.0, AssessmentScoringService::scoreDisc($test, 'finance'));
    }

    public function test_disc_score_falls_back_to_dominant_dimension_without_weights(): void
    {
        $test = new DiscTest([
            'graph_d' => 40,
            'graph_i' => 80,
            'graph_s' => 60,
            'graph_c' => 50,
        ]);

        $this->assertSame(80.0, AssessmentScoringService::scoreDisc($test, 'unknown'));
        $this->assertSame([], AssessmentScoringService::discBreakdown($test)['rows']);
    }
}
