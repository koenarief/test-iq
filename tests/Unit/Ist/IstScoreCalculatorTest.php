<?php

namespace Tests\Unit\Ist;

use App\Models\Ist\IstAnswer;
use App\Support\Ist\IstScoreCalculator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class IstScoreCalculatorTest extends TestCase
{
    private IstScoreCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = new IstScoreCalculator;
    }

    public function test_binary_scoring_distinguishes_correct_wrong_and_blank(): void
    {
        $this->assertSame(
            ['awarded_score' => 1, 'outcome' => IstAnswer::OUTCOME_CORRECT],
            $this->calculator->scoreBinary(true)
        );
        $this->assertSame(
            ['awarded_score' => 0, 'outcome' => IstAnswer::OUTCOME_WRONG],
            $this->calculator->scoreBinary(false)
        );
        $this->assertSame(
            ['awarded_score' => 0, 'outcome' => IstAnswer::OUTCOME_BLANK],
            $this->calculator->scoreBinary(null)
        );
    }

    public function test_ge_weighted_scoring_uses_the_final_outcome_rules(): void
    {
        $this->assertSame(
            ['awarded_score' => 4, 'outcome' => IstAnswer::OUTCOME_CORRECT],
            $this->calculator->scoreWeighted(4)
        );

        foreach ([1, 2, 3] as $score) {
            $this->assertSame(
                ['awarded_score' => $score, 'outcome' => IstAnswer::OUTCOME_PARTIAL],
                $this->calculator->scoreWeighted($score)
            );
        }

        $this->assertSame(
            ['awarded_score' => 0, 'outcome' => IstAnswer::OUTCOME_WRONG],
            $this->calculator->scoreWeighted(0)
        );
        $this->assertSame(
            ['awarded_score' => 0, 'outcome' => IstAnswer::OUTCOME_BLANK],
            $this->calculator->scoreWeighted(null)
        );
    }

    public function test_ge_rejects_a_weight_outside_zero_to_four(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->calculator->scoreWeighted(5);
    }

    public function test_numeric_scoring_compares_canonical_numeric_values(): void
    {
        foreach (['10', '10.0', '10.00', '0010.000', 10, 10.0] as $answer) {
            $this->assertSame(
                ['awarded_score' => 1, 'outcome' => IstAnswer::OUTCOME_CORRECT],
                $this->calculator->scoreNumeric($answer, '10.000000')
            );
        }

        $this->assertSame(
            ['awarded_score' => 1, 'outcome' => IstAnswer::OUTCOME_CORRECT],
            $this->calculator->scoreNumeric('-0.50', '-.5')
        );
    }

    public function test_numeric_scoring_distinguishes_wrong_and_blank_answers(): void
    {
        $this->assertSame(
            ['awarded_score' => 0, 'outcome' => IstAnswer::OUTCOME_WRONG],
            $this->calculator->scoreNumeric('10.01', '10')
        );
        $this->assertSame(
            ['awarded_score' => 0, 'outcome' => IstAnswer::OUTCOME_BLANK],
            $this->calculator->scoreNumeric('', '10')
        );
        $this->assertSame(
            ['awarded_score' => 0, 'outcome' => IstAnswer::OUTCOME_BLANK],
            $this->calculator->scoreNumeric(null, '10')
        );
    }
}
