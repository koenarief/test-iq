<?php

namespace Tests\Unit\Ist;

use App\Support\Ist\IstScoreCalculator;
use PHPUnit\Framework\TestCase;

class IstAggregateScoreTest extends TestCase
{
    private IstScoreCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = new IstScoreCalculator;
    }

    public function test_percentage_uses_awarded_score_divided_by_max_score(): void
    {
        $this->assertSame(75.0, $this->calculator->percentage(3, 4));
        $this->assertSame(33.333, $this->calculator->percentage(1, 3));
        $this->assertSame(66.667, $this->calculator->percentage(2, 3));
    }

    public function test_zero_max_score_does_not_divide_by_zero(): void
    {
        $this->assertSame(0.0, $this->calculator->percentage(0, 0));
        $this->assertSame(0.0, $this->calculator->percentage(10, 0));
    }

    public function test_total_internal_score_is_null_until_exactly_nine_subtests_exist(): void
    {
        $this->assertNull(
            $this->calculator->totalInternalScore([])
        );

        $this->assertNull(
            $this->calculator->totalInternalScore([
                'SE' => 80,
                'WA' => 80,
                'AN' => 80,
                'GE' => 80,
                'RA' => 80,
                'ZR' => 80,
                'FA' => 80,
                'WU' => 80,
            ])
        );

        $this->assertNull(
            $this->calculator->totalInternalScore([
                'SE' => 80,
                'WA' => 80,
                'AN' => 80,
                'GE' => 80,
                'RA' => 80,
                'ZR' => 80,
                'FA' => 80,
                'WU' => 80,
                'ME' => 80,
                'EXTRA' => 80,
            ])
        );
    }

    public function test_area_scores_are_calculated_from_nine_subtests(): void
    {
        $subtests = [
            'SE' => 72,
            'WA' => 84,
            'AN' => 68,
            'GE' => 81,
            'RA' => 76,
            'ZR' => 88,
            'FA' => 76,
            'WU' => 80,
            'ME' => 84,
        ];

        $areas = $this->calculator->areaScores($subtests);

        $this->assertSame(76.25, $areas['verbal']);
        $this->assertSame(82.0, $areas['numeric']);
        $this->assertSame(78.0, $areas['figural']);
        $this->assertSame(84.0, $areas['memory']);
    }

    public function test_cognitive_performance_index_is_mean_of_four_areas(): void
    {
        $areas = [
            'verbal' => 76.25,
            'numeric' => 82,
            'figural' => 78,
            'memory' => 84,
        ];

        $this->assertSame(
            80.063,
            $this->calculator->cognitivePerformanceIndex($areas)
        );
    }

    public function test_total_internal_score_now_returns_cognitive_performance_index(): void
    {
        $subtests = [
            'SE' => 72,
            'WA' => 84,
            'AN' => 68,
            'GE' => 81,
            'RA' => 76,
            'ZR' => 88,
            'FA' => 76,
            'WU' => 80,
            'ME' => 84,
        ];

        $this->assertSame(
            80.063,
            $this->calculator->totalInternalScore($subtests)
        );
    }

    public function test_performance_category_uses_final_score_ranges(): void
    {
        $this->assertSame(
            'Sangat Unggul',
            $this->calculator->performanceCategory(85)
        );

        $this->assertSame(
            'Unggul',
            $this->calculator->performanceCategory(80)
        );

        $this->assertSame(
            'Baik',
            $this->calculator->performanceCategory(70)
        );

        $this->assertSame(
            'Cukup',
            $this->calculator->performanceCategory(60)
        );

        $this->assertSame(
            'Perlu Pengembangan',
            $this->calculator->performanceCategory(50)
        );

        $this->assertSame(
            'Perlu Perhatian',
            $this->calculator->performanceCategory(30)
        );
    }

    public function test_performance_benchmark_uses_final_score_ranges(): void
    {
        $this->assertSame(
            'Jauh di Atas Rata-rata',
            $this->calculator->performanceBenchmark(90)
        );

        $this->assertSame(
            'Di Atas Rata-rata',
            $this->calculator->performanceBenchmark(80)
        );

        $this->assertSame(
            'Rata-rata',
            $this->calculator->performanceBenchmark(70)
        );

        $this->assertSame(
            'Di Bawah Rata-rata',
            $this->calculator->performanceBenchmark(50)
        );

        $this->assertSame(
            'Jauh di Bawah Rata-rata',
            $this->calculator->performanceBenchmark(40)
        );
    }

    public function test_strongest_and_development_areas_are_derived_from_area_scores(): void
    {
        $areas = [
            'verbal' => 76.25,
            'numeric' => 82,
            'figural' => 78,
            'memory' => 84,
        ];

        $this->assertSame(
            ['memory', 'numeric'],
            $this->calculator->strongestAreas($areas)
        );

        $this->assertSame(
            ['verbal'],
            $this->calculator->developmentAreas($areas)
        );
    }

    public function test_profile_spread_and_balance_label_are_calculated(): void
    {
        $areas = [
            'verbal' => 76.25,
            'numeric' => 82,
            'figural' => 78,
            'memory' => 84,
        ];

        $spread = $this->calculator->profileSpread($areas);

        $this->assertSame(7.75, $spread);

        $this->assertSame(
            'Relatif Seimbang',
            $this->calculator->profileBalanceLabel($spread)
        );

        $this->assertSame(
            'Terdapat Variasi Kemampuan',
            $this->calculator->profileBalanceLabel(15)
        );

        $this->assertSame(
            'Perbedaan Kemampuan Cukup Menonjol',
            $this->calculator->profileBalanceLabel(25)
        );
    }

    public function test_age_group_is_informational(): void
    {
        $this->assertSame(
            '18–24 tahun',
            $this->calculator->ageGroup(18)
        );

        $this->assertSame(
            '25–34 tahun',
            $this->calculator->ageGroup(30)
        );

        $this->assertSame(
            '35–44 tahun',
            $this->calculator->ageGroup(40)
        );

        $this->assertSame(
            '45–54 tahun',
            $this->calculator->ageGroup(50)
        );

        $this->assertSame(
            '55+ tahun',
            $this->calculator->ageGroup(60)
        );
    }
}