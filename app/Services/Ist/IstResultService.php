<?php

namespace App\Services\Ist;

use App\Data\Ist\IstResultData;
use App\Data\Ist\IstSubtestResultData;
use App\Exceptions\Ist\IstResultUnavailableException;
use App\Models\Ist\IstTest;
use App\Models\Ist\IstTestSubtest;
use App\Support\Ist\IstScoreCalculator;
use Carbon\CarbonImmutable;

final class IstResultService
{
    public function __construct(
        private readonly IstScoreCalculator $calculator,
    ) {}

    public function build(IstTest $test): IstResultData
    {
        if (! $test->exists || ! $test->getKey()) {
            throw new IstResultUnavailableException(
                0,
                'test does not exist'
            );
        }

        $test = IstTest::query()
            ->with([
                'subtests' => fn ($query) => $query
                    ->with('subtest')
                    ->orderBy('sequence')
            ])
            ->find($test->getKey());

        if (! $test) {
            throw new IstResultUnavailableException(
                0,
                'test is unavailable'
            );
        }

        $this->assertResultIsConsistent($test);

        $startedAt = CarbonImmutable::instance(
            $test->started_at
        );

        $finishedAt = CarbonImmutable::instance(
            $test->finished_at
        );

        $durationSeconds =
            $finishedAt->getTimestamp()
            - $startedAt->getTimestamp();

        if ($durationSeconds < 0) {
            throw new IstResultUnavailableException(
                $test->id,
                'test duration is negative'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 9 Subtest Results
        |--------------------------------------------------------------------------
        */

        $subtests = $test->subtests
            ->map(function (
                IstTestSubtest $runtime
            ) use ($test): IstSubtestResultData {
                if (! $runtime->subtest) {
                    throw new IstResultUnavailableException(
                        $test->id,
                        'master subtest is unavailable'
                    );
                }

                return new IstSubtestResultData(
                    code: strtoupper(
                        trim((string) $runtime->subtest->code)
                    ),
                    name: $runtime->subtest->name,
                    sequence: $runtime->sequence,
                    awardedScore: (float) $runtime->awarded_score,
                    maxScore: (float) $runtime->max_score,
                    correctCount: $runtime->correct_count,
                    partialCount: $runtime->partial_count,
                    wrongCount: $runtime->wrong_count,
                    blankCount: $runtime->blank_count,
                    percentage: (float) $runtime->percentage,
                );
            })
            ->values()
            ->all();

        /*
        |--------------------------------------------------------------------------
        | 9 Subtest Graph
        |--------------------------------------------------------------------------
        */

        $graphPoints = array_map(
            static fn (
                IstSubtestResultData $subtest
            ): array => [
                'code' => $subtest->code,
                'percentage' => $subtest->percentage,
            ],
            $subtests,
        );

        /*
        |--------------------------------------------------------------------------
        | Build keyed subtest scores
        |--------------------------------------------------------------------------
        */

        $subtestScores = [];

        foreach ($subtests as $subtest) {
            $subtestScores[$subtest->code] =
                $subtest->percentage;
        }

        if (count($subtestScores) !== 9) {
            throw new IstResultUnavailableException(
                $test->id,
                'exactly nine unique subtest scores are required'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 4 Cognitive Areas
        |--------------------------------------------------------------------------
        */

        $areaScores = $this->calculator->areaScores(
            $subtestScores
        );

        $areaGraphPoints = [
            [
                'key' => 'verbal',
                'label' => 'Verbal',
                'percentage' => $areaScores['verbal'],
            ],
            [
                'key' => 'numeric',
                'label' => 'Numerik',
                'percentage' => $areaScores['numeric'],
            ],
            [
                'key' => 'figural',
                'label' => 'Figural',
                'percentage' => $areaScores['figural'],
            ],
            [
                'key' => 'memory',
                'label' => 'Memori',
                'percentage' => $areaScores['memory'],
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | Cognitive Performance Index
        |--------------------------------------------------------------------------
        */

        $calculatedIndex =
            $this->calculator
                ->cognitivePerformanceIndex($areaScores);

        $storedIndex =
            (float) $test->total_internal_score;

        /*
         * The stored result was produced during finalization.
         * Recalculate here as a consistency check.
         */
        if (
            abs($calculatedIndex - $storedIndex)
            > 0.001
        ) {
            throw new IstResultUnavailableException(
                $test->id,
                'stored cognitive performance index is inconsistent'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Interpretation
        |--------------------------------------------------------------------------
        */

        $performanceCategory =
            $this->calculator
                ->performanceCategory($storedIndex);

        $performanceBenchmark =
            $this->calculator
                ->performanceBenchmark($storedIndex);

        $strongestAreas =
            $this->calculator
                ->strongestAreas($areaScores);

        $developmentAreas =
            $this->calculator
                ->developmentAreas($areaScores);

        $profileSpread =
            $this->calculator
                ->profileSpread($areaScores);

        $profileBalanceLabel =
            $this->calculator
                ->profileBalanceLabel($profileSpread);

        $ageGroup =
            $this->calculator
                ->ageGroup($test->age);

        /*
        |--------------------------------------------------------------------------
        | Final Result DTO
        |--------------------------------------------------------------------------
        */

        return new IstResultData(
            participantName: $test->participant_name,
            age: $test->age,
            gender: $test->gender,
            ageGroup: $ageGroup,

            startedAt: $startedAt,
            finishedAt: $finishedAt,
            durationSeconds: $durationSeconds,

            subtests: $subtests,
            graphPoints: $graphPoints,

            areaScores: $areaScores,
            areaGraphPoints: $areaGraphPoints,

            totalInternalScore: $storedIndex,

            performanceCategory: $performanceCategory,
            performanceBenchmark: $performanceBenchmark,

            strongestAreas: $strongestAreas,
            developmentAreas: $developmentAreas,

            profileSpread: $profileSpread,
            profileBalanceLabel: $profileBalanceLabel,
        );
    }

    private function assertResultIsConsistent(
        IstTest $test
    ): void {
        if (
            $test->status
            !== IstTest::STATUS_COMPLETED
        ) {
            throw new IstResultUnavailableException(
                $test->id,
                'test is not completed'
            );
        }

        if ($test->started_at === null) {
            throw new IstResultUnavailableException(
                $test->id,
                'start time is unavailable'
            );
        }

        if ($test->finished_at === null) {
            throw new IstResultUnavailableException(
                $test->id,
                'finish time is unavailable'
            );
        }

        if (
            (float) $test->finished_at->format('U.u')
            <
            (float) $test->started_at->format('U.u')
        ) {
            throw new IstResultUnavailableException(
                $test->id,
                'finish time precedes start time'
            );
        }

        if ($test->total_internal_score === null) {
            throw new IstResultUnavailableException(
                $test->id,
                'overall internal score is unavailable'
            );
        }

        if ($test->subtests->count() !== 9) {
            throw new IstResultUnavailableException(
                $test->id,
                'exactly nine runtime subtests are required'
            );
        }

        foreach ($test->subtests as $runtime) {
            if (
                ! in_array(
                    $runtime->status,
                    [
                        IstTestSubtest::STATUS_COMPLETED,
                        IstTestSubtest::STATUS_TIMED_OUT,
                    ],
                    true,
                )
            ) {
                throw new IstResultUnavailableException(
                    $test->id,
                    'all runtime subtests must be finalized'
                );
            }

            if ($runtime->percentage === null) {
                throw new IstResultUnavailableException(
                    $test->id,
                    'a subtest percentage is unavailable'
                );
            }

            if (! $runtime->subtest) {
                throw new IstResultUnavailableException(
                    $test->id,
                    'master subtest is unavailable'
                );
            }
        }
    }
}