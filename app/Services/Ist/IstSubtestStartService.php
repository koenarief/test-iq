<?php

namespace App\Services\Ist;

use App\Exceptions\Ist\IncompleteIstSnapshotException;
use App\Exceptions\Ist\InvalidIstSubtestStartException;
use App\Models\Ist\IstTest;
use App\Models\Ist\IstTestSubtest;
use App\Support\Ist\IstMeRuntimeContent;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

final class IstSubtestStartService
{
    public function __construct(
        private readonly IstMeRuntimeContent $meRuntimeContent,
    ) {}

    public function start(
        IstTestSubtest $testSubtest,
        CarbonInterface $now,
    ): IstTestSubtest {
        if (! $testSubtest->exists || ! $testSubtest->getKey()) {
            throw new InvalidIstSubtestStartException(0, 'runtime subtest does not exist');
        }

        return DB::transaction(function () use ($testSubtest, $now): IstTestSubtest {
            $runtime = IstTestSubtest::query()
                ->with('subtest')
                ->lockForUpdate()
                ->find($testSubtest->getKey());

            if (! $runtime || ! $runtime->subtest) {
                throw new InvalidIstSubtestStartException(
                    (int) $testSubtest->getKey(),
                    'master subtest is unavailable',
                );
            }

            $test = IstTest::query()
                ->lockForUpdate()
                ->find($runtime->ist_test_id);

            if (! $test) {
                throw new InvalidIstSubtestStartException($runtime->id, 'parent test is unavailable');
            }

            $this->assertOverallTestCanStart($test, $runtime);

            if ($runtime->sequence !== $test->current_subtest_sequence) {
                throw new InvalidIstSubtestStartException($runtime->id, 'subtest is not current');
            }

            if ($runtime->locked_at !== null) {
                throw new InvalidIstSubtestStartException($runtime->id, 'subtest is locked');
            }

            if ($runtime->subtest->code === 'ME'
                && $runtime->started_at === null
                && $runtime->instruction_viewed_at === null) {
                throw new InvalidIstSubtestStartException(
                    $runtime->id,
                    'ME example has not been completed',
                );
            }

            $snapshotCount = $runtime->testQuestions()->count();

            if ($snapshotCount !== $runtime->question_count) {
                throw new IncompleteIstSnapshotException(
                    $runtime->id,
                    $runtime->question_count,
                    $snapshotCount,
                );
            }

            if ($runtime->subtest->code === 'ME') {
                $firstSnapshot = $runtime->testQuestions()
                    ->orderBy('display_order')
                    ->first(['question_snapshot']);

                if ($this->meRuntimeContent->fromQuestionSnapshot(
                    $firstSnapshot?->question_snapshot,
                ) === null) {
                    throw new InvalidIstSubtestStartException(
                        $runtime->id,
                        'ME category runtime snapshot is unavailable',
                    );
                }
            }

            if ($runtime->started_at !== null) {
                $this->assertStartedRuntimeIsConsistent($runtime);

                return $runtime->fresh();
            }

            $this->assertUnstartedRuntimeIsConsistent($runtime);

            $startedAt = CarbonImmutable::instance($now);

            if ($runtime->subtest->code === 'ME') {
                $memorizationEndsAt = $startedAt->addSeconds(
                    $runtime->subtest->memorization_seconds,
                );

                $runtime->update([
                    'started_at' => $startedAt,
                    'memorization_started_at' => $startedAt,
                    'memorization_ends_at' => $memorizationEndsAt,
                    'answering_started_at' => $memorizationEndsAt,
                    'answering_ends_at' => $memorizationEndsAt->addSeconds(
                        $runtime->subtest->answering_seconds,
                    ),
                    'status' => IstTestSubtest::STATUS_MEMORIZING,
                ]);
            } else {
                $runtime->update([
                    'started_at' => $startedAt,
                    'answering_started_at' => $startedAt,
                    'answering_ends_at' => $startedAt->addSeconds(
                        $runtime->subtest->answering_seconds,
                    ),
                    'status' => IstTestSubtest::STATUS_ANSWERING,
                ]);
            }

            if ($runtime->sequence === 1 && $runtime->subtest->code === 'SE') {
                $test->update([
                    'status' => IstTest::STATUS_IN_PROGRESS,
                    'started_at' => $startedAt,
                ]);
            }

            return $runtime->fresh();
        });
    }

    private function assertOverallTestCanStart(IstTest $test, IstTestSubtest $runtime): void
    {
        if (in_array($test->status, [
            IstTest::STATUS_COMPLETED,
            IstTest::STATUS_CANCELLED,
        ], true)) {
            throw new InvalidIstSubtestStartException($runtime->id, 'parent test is closed');
        }

        if ($test->status === IstTest::STATUS_IN_PROGRESS && $test->started_at === null) {
            throw new InvalidIstSubtestStartException(
                $runtime->id,
                'parent test is in progress without a start time',
            );
        }

        if ($test->status === IstTest::STATUS_DRAFT && $test->started_at !== null) {
            throw new InvalidIstSubtestStartException(
                $runtime->id,
                'draft parent test already has a start time',
            );
        }

        if (! in_array($test->status, [
            IstTest::STATUS_DRAFT,
            IstTest::STATUS_IN_PROGRESS,
        ], true)) {
            throw new InvalidIstSubtestStartException($runtime->id, 'parent test status is invalid');
        }

        if ($runtime->sequence === 1 && $runtime->subtest->code === 'SE') {
            return;
        }

        if ($test->status !== IstTest::STATUS_IN_PROGRESS || $test->started_at === null) {
            throw new InvalidIstSubtestStartException(
                $runtime->id,
                'later subtests require a consistently started parent test',
            );
        }
    }

    private function assertUnstartedRuntimeIsConsistent(IstTestSubtest $runtime): void
    {
        if ($runtime->status !== IstTestSubtest::STATUS_INSTRUCTION) {
            throw new InvalidIstSubtestStartException(
                $runtime->id,
                'unstarted subtest must be in instruction status',
            );
        }

        foreach ([
            'memorization_started_at',
            'memorization_ends_at',
            'answering_started_at',
            'answering_ends_at',
        ] as $field) {
            if ($runtime->{$field} !== null) {
                throw new InvalidIstSubtestStartException(
                    $runtime->id,
                    'unstarted subtest contains timer data',
                );
            }
        }
    }

    private function assertStartedRuntimeIsConsistent(IstTestSubtest $runtime): void
    {
        if ($runtime->subtest->code === 'ME') {
            $requiredFields = [
                'memorization_started_at',
                'memorization_ends_at',
                'answering_started_at',
                'answering_ends_at',
            ];
            $validStatuses = [
                IstTestSubtest::STATUS_MEMORIZING,
                IstTestSubtest::STATUS_ANSWERING,
            ];
        } else {
            $requiredFields = ['answering_started_at', 'answering_ends_at'];
            $validStatuses = [IstTestSubtest::STATUS_ANSWERING];

            if ($runtime->memorization_started_at !== null
                || $runtime->memorization_ends_at !== null) {
                throw new InvalidIstSubtestStartException(
                    $runtime->id,
                    'non-ME subtest contains memorization timer data',
                );
            }
        }

        foreach ($requiredFields as $field) {
            if ($runtime->{$field} === null) {
                throw new InvalidIstSubtestStartException(
                    $runtime->id,
                    'started subtest has incomplete timer data',
                );
            }
        }

        if (! in_array($runtime->status, $validStatuses, true)) {
            throw new InvalidIstSubtestStartException(
                $runtime->id,
                'started subtest status is inconsistent',
            );
        }
    }
}
