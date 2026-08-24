<?php

namespace App\Services\Ist;

use App\Data\Ist\IstResultData;
use App\Data\Ist\IstSubtestResultData;
use App\Exceptions\Ist\IstNormLookupException;
use App\Exceptions\Ist\IstResultUnavailableException;
use App\Models\Ist\IstTest;
use App\Models\Ist\IstTestSubtest;
use App\Support\Ist\IstScoreCalculator;
use Carbon\CarbonImmutable;

final class IstResultService
{
    public function __construct(
        private readonly IstScoreCalculator $calculator,
        private readonly IstScoringService $scoring,
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
        | Raw score (RW) per subtest
        |--------------------------------------------------------------------------
        |
        | Difficulty weighting has been removed from finalization, so each
        | subtest's awarded_score is already the flat raw score (1 point per
        | correct item, or 0-3 per GE item).
        */

        $rawScores = [];

        foreach ($test->subtests as $runtime) {
            if (! $runtime->subtest) {
                throw new IstResultUnavailableException(
                    $test->id,
                    'master subtest is unavailable'
                );
            }

            $rawScores[strtoupper(trim((string) $runtime->subtest->code))]
                = (int) round((float) $runtime->awarded_score);
        }

        if (count($rawScores) !== 9) {
            throw new IstResultUnavailableException(
                $test->id,
                'exactly nine unique subtest scores are required'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Standard score (SW) / IQ / dominance profile
        |--------------------------------------------------------------------------
        |
        | Computed on demand from IST age norm tables. Norm data may not yet
        | cover every age/subtest/raw-score combination, so a missing lookup
        | degrades to a null IQ rather than blocking the whole result page or
        | guessing a fake score.
        */

        try {
            $scoring = $this->scoring->calculateFromRawScores($rawScores, $test->age);
            $standardScores = $scoring['standard_scores'];
            $totalStandardScore = $scoring['total_standard_score'];
            $iqScore = $scoring['iq_score'];
            $iqCategory = $scoring['iq_category'];
            $dominanceProfile = $scoring['dominance_profile'];
        } catch (IstNormLookupException) {
            $standardScores = array_fill_keys(array_keys($rawScores), null);
            $totalStandardScore = null;
            $iqScore = null;
            $iqCategory = null;
            $dominanceProfile = null;
        }

        /*
        |--------------------------------------------------------------------------
        | 9 Subtest Results
        |--------------------------------------------------------------------------
        */

        $subtests = $test->subtests
            ->map(function (
                IstTestSubtest $runtime
            ) use ($rawScores, $standardScores): IstSubtestResultData {
                $code = strtoupper(trim((string) $runtime->subtest->code));

                return new IstSubtestResultData(
                    code: $code,
                    name: $runtime->subtest->name,
                    sequence: $runtime->sequence,
                    rawScore: $rawScores[$code],
                    standardScore: $standardScores[$code] ?? null,
                    correctCount: $runtime->correct_count,
                    partialCount: $runtime->partial_count,
                    wrongCount: $runtime->wrong_count,
                    blankCount: $runtime->blank_count,
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
                'standardScore' => $subtest->standardScore,
            ],
            $subtests,
        );

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

            totalRawScore: array_sum($rawScores),
            totalStandardScore: $totalStandardScore,
            iqScore: $iqScore,
            iqCategory: $iqCategory,
            dominanceProfile: $dominanceProfile,
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

            if (! $runtime->subtest) {
                throw new IstResultUnavailableException(
                    $test->id,
                    'master subtest is unavailable'
                );
            }
        }
    }
}
