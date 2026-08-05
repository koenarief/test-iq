<?php

namespace App\Services\Ist;

use App\Data\Ist\IstResultData;
use App\Data\Ist\IstSubtestResultData;
use App\Exceptions\Ist\IstResultUnavailableException;
use App\Models\Ist\IstTest;
use App\Models\Ist\IstTestSubtest;
use Carbon\CarbonImmutable;

final class IstResultService
{
    public function build(IstTest $test): IstResultData
    {
        if (! $test->exists || ! $test->getKey()) {
            throw new IstResultUnavailableException(0, 'test does not exist');
        }

        $test = IstTest::query()
            ->with(['subtests' => fn ($query) => $query
                ->with('subtest')
                ->orderBy('sequence')])
            ->find($test->getKey());

        if (! $test) {
            throw new IstResultUnavailableException(0, 'test is unavailable');
        }

        $this->assertResultIsConsistent($test);

        $startedAt = CarbonImmutable::instance($test->started_at);
        $finishedAt = CarbonImmutable::instance($test->finished_at);
        $durationSeconds = $finishedAt->getTimestamp() - $startedAt->getTimestamp();

        if ($durationSeconds < 0) {
            throw new IstResultUnavailableException($test->id, 'test duration is negative');
        }

        $subtests = $test->subtests
            ->map(function (IstTestSubtest $runtime) use ($test): IstSubtestResultData {
                if (! $runtime->subtest) {
                    throw new IstResultUnavailableException($test->id, 'master subtest is unavailable');
                }

                return new IstSubtestResultData(
                    code: $runtime->subtest->code,
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

        $graphPoints = array_map(
            static fn (IstSubtestResultData $subtest): array => [
                'code' => $subtest->code,
                'percentage' => $subtest->percentage,
            ],
            $subtests,
        );

        return new IstResultData(
            participantName: $test->participant_name,
            age: $test->age,
            gender: $test->gender,
            startedAt: $startedAt,
            finishedAt: $finishedAt,
            durationSeconds: $durationSeconds,
            subtests: $subtests,
            graphPoints: $graphPoints,
            totalInternalScore: (float) $test->total_internal_score,
        );
    }

    private function assertResultIsConsistent(IstTest $test): void
    {
        if ($test->status !== IstTest::STATUS_COMPLETED) {
            throw new IstResultUnavailableException($test->id, 'test is not completed');
        }

        if ($test->started_at === null) {
            throw new IstResultUnavailableException($test->id, 'start time is unavailable');
        }

        if ($test->finished_at === null) {
            throw new IstResultUnavailableException($test->id, 'finish time is unavailable');
        }

        if ((float) $test->finished_at->format('U.u') < (float) $test->started_at->format('U.u')) {
            throw new IstResultUnavailableException($test->id, 'finish time precedes start time');
        }

        if ($test->total_internal_score === null) {
            throw new IstResultUnavailableException($test->id, 'overall internal score is unavailable');
        }

        if ($test->subtests->count() !== 9) {
            throw new IstResultUnavailableException($test->id, 'exactly nine runtime subtests are required');
        }

        foreach ($test->subtests as $runtime) {
            if (! in_array($runtime->status, [
                IstTestSubtest::STATUS_COMPLETED,
                IstTestSubtest::STATUS_TIMED_OUT,
            ], true)) {
                throw new IstResultUnavailableException($test->id, 'all runtime subtests must be finalized');
            }

            if ($runtime->percentage === null) {
                throw new IstResultUnavailableException($test->id, 'a subtest percentage is unavailable');
            }
        }
    }
}
