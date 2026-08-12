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

    public function test_binary_scoring_uses_difficulty_weights(): void
    {
        $this->assertSame(
            ['awarded_score' => 1, 'outcome' => IstAnswer::OUTCOME_CORRECT],
            $this->calculator->scoreBinary(true, 'easy')
        );

        $this->assertSame(
            ['awarded_score' => 2, 'outcome' => IstAnswer::OUTCOME_CORRECT],
            $this->calculator->scoreBinary(true, 'medium')
        );

        $this->assertSame(
            ['awarded_score' => 3, 'outcome' => IstAnswer::OUTCOME_CORRECT],
            $this->calculator->scoreBinary(true, 'hard')
        );

        $this->assertSame(
            ['awarded_score' => 0, 'outcome' => IstAnswer::OUTCOME_WRONG],
            $this->calculator->scoreBinary(false, 'hard')
        );

        $this->assertSame(
            ['awarded_score' => 0, 'outcome' => IstAnswer::OUTCOME_BLANK],
            $this->calculator->scoreBinary(null, 'hard')
        );
    }

    public function test_ge_weighted_scoring_uses_difficulty_weights(): void
    {
        $this->assertSame(
            ['awarded_score' => 4, 'outcome' => IstAnswer::OUTCOME_CORRECT],
            $this->calculator->scoreWeighted(4, 'easy')
        );

        $this->assertSame(
            ['awarded_score' => 8, 'outcome' => IstAnswer::OUTCOME_CORRECT],
            $this->calculator->scoreWeighted(4, 'medium')
        );

        $this->assertSame(
            ['awarded_score' => 12, 'outcome' => IstAnswer::OUTCOME_CORRECT],
            $this->calculator->scoreWeighted(4, 'hard')
        );

        foreach ([1, 2, 3] as $score) {
            $this->assertSame(
                [
                    'awarded_score' => $score * 3,
                    'outcome' => IstAnswer::OUTCOME_PARTIAL,
                ],
                $this->calculator->scoreWeighted($score, 'hard')
            );
        }

        $this->assertSame(
            ['awarded_score' => 0, 'outcome' => IstAnswer::OUTCOME_WRONG],
            $this->calculator->scoreWeighted(0, 'hard')
        );

        $this->assertSame(
            ['awarded_score' => 0, 'outcome' => IstAnswer::OUTCOME_BLANK],
            $this->calculator->scoreWeighted(null, 'hard')
        );
    }

    public function test_ge_rejects_a_weight_outside_zero_to_four(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->calculator->scoreWeighted(5, 'medium');
    }

    public function test_numeric_scoring_compares_canonical_numeric_values(): void
    {
        foreach (['10', '10.0', '10.00', '0010.000', 10, 10.0] as $answer) {
            $this->assertSame(
                ['awarded_score' => 3, 'outcome' => IstAnswer::OUTCOME_CORRECT],
                $this->calculator->scoreNumeric(
                    $answer,
                    '10.000000',
                    'hard'
                )
            );
        }

        $this->assertSame(
            ['awarded_score' => 2, 'outcome' => IstAnswer::OUTCOME_CORRECT],
            $this->calculator->scoreNumeric(
                '-0.50',
                '-.5',
                'medium'
            )
        );
    }

    public function test_numeric_scoring_distinguishes_wrong_and_blank_answers(): void
    {
        $this->assertSame(
            ['awarded_score' => 0, 'outcome' => IstAnswer::OUTCOME_WRONG],
            $this->calculator->scoreNumeric(
                '10.01',
                '10',
                'hard'
            )
        );

        $this->assertSame(
            ['awarded_score' => 0, 'outcome' => IstAnswer::OUTCOME_BLANK],
            $this->calculator->scoreNumeric(
                '',
                '10',
                'hard'
            )
        );

        $this->assertSame(
            ['awarded_score' => 0, 'outcome' => IstAnswer::OUTCOME_BLANK],
            $this->calculator->scoreNumeric(
                null,
                '10',
                'hard'
            )
        );
    }

    public function test_weighted_max_score_uses_difficulty_weights(): void
    {
        $this->assertSame(
            1.0,
            $this->calculator->weightedMaxScore(1, 'easy')
        );

        $this->assertSame(
            2.0,
            $this->calculator->weightedMaxScore(1, 'medium')
        );

        $this->assertSame(
            3.0,
            $this->calculator->weightedMaxScore(1, 'hard')
        );

        $this->assertSame(
            4.0,
            $this->calculator->weightedMaxScore(4, 'easy')
        );

        $this->assertSame(
            8.0,
            $this->calculator->weightedMaxScore(4, 'medium')
        );

        $this->assertSame(
            12.0,
            $this->calculator->weightedMaxScore(4, 'hard')
        );
    }

    public function test_percentage_uses_weighted_score_and_weighted_max_score(): void
    {
        $this->assertSame(
            75.0,
            $this->calculator->percentage(6, 8)
        );

        $this->assertSame(
            0.0,
            $this->calculator->percentage(0, 0)
        );
    }

    public function test_invalid_difficulty_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->calculator->scoreBinary(
            true,
            'very_hard'
        );
    }
}
