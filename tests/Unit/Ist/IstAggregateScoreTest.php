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

    public function test_total_internal_score_is_null_until_exactly_nine_percentages_exist(): void
    {
        $this->assertNull($this->calculator->totalInternalScore([]));
        $this->assertNull($this->calculator->totalInternalScore(array_fill(0, 8, 80)));
        $this->assertNull($this->calculator->totalInternalScore(array_fill(0, 10, 80)));
    }

    public function test_total_internal_score_is_the_rounded_average_of_nine_percentages(): void
    {
        $percentages = [100, 90, 80, 70, 60, 50, 40, 30, 20];

        $this->assertSame(60.0, $this->calculator->totalInternalScore($percentages));

        $rounded = $this->calculator->totalInternalScore([
            33.333, 66.667, 10.111, 20.222, 30.333,
            40.444, 50.555, 60.666, 70.777,
        ]);

        $this->assertSame(42.568, $rounded);
    }
}
