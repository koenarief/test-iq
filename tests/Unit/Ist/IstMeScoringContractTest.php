<?php

namespace Tests\Unit\Ist;

use App\Models\Ist\IstAnswer;
use App\Support\Ist\IstScoreCalculator;
use PHPUnit\Framework\TestCase;

final class IstMeScoringContractTest extends TestCase
{
    private IstScoreCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = new IstScoreCalculator();
    }

    public function test_final_me_weighted_maximum_is_23_and_example_is_excluded(): void
    {
        $dataset = $this->dataset();
        $examples = $this->questionsByKind($dataset, 'example');
        $scored = $this->questionsByKind($dataset, 'scored');

        $this->assertCount(1, $examples);
        $this->assertCount(12, $scored);

        $maximum = array_sum(array_map(
            fn (array $question): float => $this->calculator->weightedMaxScore(
                $question['scoring']['max_score'],
                $question['difficulty_target'],
            ),
            $scored,
        ));

        $this->assertSame(23.0, $maximum);
        $this->assertSame(100.0, $this->calculator->percentage(23, $maximum));
    }

    public function test_me_binary_outcomes_use_difficulty_weight_without_partial_credit(): void
    {
        $this->assertSame(
            ['awarded_score' => 1, 'outcome' => IstAnswer::OUTCOME_CORRECT],
            $this->calculator->scoreBinary(true, 'easy'),
        );
        $this->assertSame(
            ['awarded_score' => 2, 'outcome' => IstAnswer::OUTCOME_CORRECT],
            $this->calculator->scoreBinary(true, 'medium'),
        );
        $this->assertSame(
            ['awarded_score' => 3, 'outcome' => IstAnswer::OUTCOME_CORRECT],
            $this->calculator->scoreBinary(true, 'hard'),
        );
        $this->assertSame(
            ['awarded_score' => 0, 'outcome' => IstAnswer::OUTCOME_WRONG],
            $this->calculator->scoreBinary(false, 'hard'),
        );
        $this->assertSame(
            ['awarded_score' => 0, 'outcome' => IstAnswer::OUTCOME_BLANK],
            $this->calculator->scoreBinary(null, 'hard'),
        );
    }

    public function test_me_sample_score_is_13_of_23_or_56_522_percent(): void
    {
        $awarded = (4 * $this->calculator->difficultyWeight('easy'))
            + (3 * $this->calculator->difficultyWeight('medium'))
            + $this->calculator->difficultyWeight('hard');

        $this->assertSame(13, $awarded);
        $this->assertSame(56.522, $this->calculator->percentage($awarded, 23));
    }

    public function test_me_is_memory_domain_and_total_uses_four_equal_domains(): void
    {
        $subtests = [
            'SE' => 60,
            'WA' => 70,
            'AN' => 80,
            'GE' => 90,
            'RA' => 50,
            'ZR' => 70,
            'FA' => 40,
            'WU' => 60,
            'ME' => 56.522,
        ];

        $areas = $this->calculator->areaScores($subtests);

        $this->assertSame([
            'verbal' => 75.0,
            'numeric' => 60.0,
            'figural' => 50.0,
            'memory' => 56.522,
        ], $areas);
        $this->assertSame(60.381, $this->calculator->totalInternalScore($subtests));
        $this->assertNotSame(
            round(array_sum($subtests) / 9, 3),
            $this->calculator->totalInternalScore($subtests),
        );
    }

    private function dataset(): array
    {
        $contents = file_get_contents(
            dirname(__DIR__, 3).'/database/data/ist-final-staging/me.json',
        );

        $this->assertIsString($contents);

        return json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
    }

    private function questionsByKind(array $dataset, string $kind): array
    {
        return array_values(array_filter(
            $dataset['questions'],
            static fn (array $question): bool => $question['kind'] === $kind,
        ));
    }
}
