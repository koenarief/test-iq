<?php

namespace Tests\Feature\Ist\Services;

use App\Exceptions\Ist\IstResultUnavailableException;
use App\Models\Ist\IstSubtest;
use App\Models\Ist\IstTest;
use App\Models\Ist\IstTestSubtest;
use App\Services\Ist\IstResultService;
use App\Services\Ist\IstTestLifecycleService;
use App\Support\Ist\IstAnswerType;
use Carbon\CarbonImmutable;
use ReflectionMethod;

class IstResultServiceTest extends IstDatabaseTestCase
{
    private IstResultService $service;

    private IstTestLifecycleService $lifecycle;

    private CarbonImmutable $startedAt;

    private CarbonImmutable $finishedAt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(IstResultService::class);
        $this->lifecycle = app(IstTestLifecycleService::class);
        $this->startedAt = CarbonImmutable::parse('2026-08-05 09:00:00', 'UTC');
        $this->finishedAt = $this->startedAt->addSeconds(2701);
    }

    public function test_completed_result_is_ordered_has_graph_integer_duration_and_is_read_only(): void
    {
        $test = $this->completedTest();
        $updatedAt = $test->updated_at->toISOString();
        $runtimeTimestamps = $test->subtests()
            ->orderBy('sequence')
            ->get()
            ->mapWithKeys(fn ($runtime): array => [
                $runtime->id => $runtime->updated_at->toISOString(),
            ])
            ->all();

        $result = $this->service->build($test);

        $this->assertSame(2701, $result->durationSeconds);
        $this->assertIsInt($result->durationSeconds);
        $this->assertGreaterThanOrEqual(0, $result->durationSeconds);
        $this->assertSame(['SE', 'WA', 'AN', 'GE', 'RA', 'ZR', 'FA', 'WU', 'ME'], array_map(
            static fn ($subtest): string => $subtest->code,
            $result->subtests,
        ));
        $this->assertCount(9, $result->graphPoints);
        $this->assertSame(['code' => 'SE', 'percentage' => 10.0], $result->graphPoints[0]);
        $this->assertSame([
            'verbal' => 25.0,
            'numeric' => 55.0,
            'figural' => 75.0,
            'memory' => 90.0,
        ], $result->areaScores);

        $this->assertSame([
            [
                'key' => 'verbal',
                'label' => 'Verbal',
                'percentage' => 25.0,
            ],
            [
                'key' => 'numeric',
                'label' => 'Numerik',
                'percentage' => 55.0,
            ],
            [
                'key' => 'figural',
                'label' => 'Figural',
                'percentage' => 75.0,
            ],
            [
                'key' => 'memory',
                'label' => 'Memori',
                'percentage' => 90.0,
            ],
        ], $result->areaGraphPoints);

        $this->assertSame(61.25, $result->totalInternalScore);
        $this->assertSame('Cukup', $result->performanceCategory);
        $this->assertSame('Rata-rata', $result->performanceBenchmark);

        $this->assertSame(
            ['memory', 'figural'],
            $result->strongestAreas
        );

        $this->assertSame(
            ['verbal'],
            $result->developmentAreas
        );

        $this->assertSame(
            65.0,
            $result->profileSpread
        );

        $this->assertSame(
            'Perbedaan Kemampuan Cukup Menonjol',
            $result->profileBalanceLabel
        );

        $this->assertSame(
            '25–34 tahun',
            $result->ageGroup
        );
        $this->assertSame($updatedAt, $test->fresh()->updated_at->toISOString());
        $this->assertSame(
            $runtimeTimestamps,
            $test->subtests()
                ->orderBy('sequence')
                ->get()
                ->mapWithKeys(fn ($runtime): array => [
                    $runtime->id => $runtime->updated_at->toISOString(),
                ])
                ->all(),
        );
    }

    public function test_result_dto_json_contains_no_sensitive_or_answer_payload_fields(): void
    {
        $result = $this->service->build($this->completedTest());
        $payload = json_decode(json_encode($result, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);
        $keys = $this->allKeys($payload);

        foreach ([
            'access_token',
            'access_token_hash',
            'answer_key',
            'answer_key_snapshot',
            'selected_option_key',
            'numeric_answer',
            'options_snapshot',
            'question_snapshot',
        ] as $forbidden) {
            $this->assertNotContains($forbidden, $keys);
        }

        $this->assertArrayHasKey('participantName', $payload);
        $this->assertArrayHasKey('subtests', $payload);
        $this->assertArrayHasKey('graphPoints', $payload);
    }

    public function test_non_completed_and_missing_overall_times_are_rejected(): void
    {
        $test = $this->completedTest();
        $test->update(['status' => IstTest::STATUS_IN_PROGRESS]);
        $this->assertUnavailable($test);

        $test->update([
            'status' => IstTest::STATUS_COMPLETED,
            'started_at' => null,
        ]);
        $this->assertUnavailable($test);

        $test->update([
            'started_at' => $this->startedAt,
            'finished_at' => null,
        ]);
        $this->assertUnavailable($test);
    }

    public function test_finish_time_before_start_time_is_rejected(): void
    {
        $test = $this->completedTest();
        $test->update(['finished_at' => $this->startedAt->subSecond()]);

        $this->expectException(IstResultUnavailableException::class);
        $this->service->build($test);
    }

    public function test_fewer_or_more_than_nine_final_runtime_subtests_are_rejected(): void
    {
        $test = $this->completedTest();
        $test->subtests()->where('sequence', 9)->delete();
        $this->assertUnavailable($test);

        $other = $this->completedTest('More runtimes');
        $extraSubtest = IstSubtest::create([
            'code' => 'XX',
            'name' => 'Extra',
            'sequence' => 10,
            'question_count' => 1,
            'default_answer_type' => IstAnswerType::SINGLE_CHOICE,
            'duration_seconds' => 60,
            'memorization_seconds' => 0,
            'answering_seconds' => 60,
            'is_active' => false,
        ]);
        IstTestSubtest::create([
            'ist_test_id' => $other->id,
            'ist_subtest_id' => $extraSubtest->id,
            'sequence' => 10,
            'status' => IstTestSubtest::STATUS_COMPLETED,
            'question_count' => 1,
            'locked_at' => $this->finishedAt,
            'finalized_reason' => IstTestSubtest::FINALIZED_SUBMITTED,
            'percentage' => 50,
        ]);

        $this->assertUnavailable($other);
    }

    public function test_non_final_runtime_and_null_percentage_are_rejected(): void
    {
        $test = $this->completedTest();
        $test->subtests()->where('sequence', 5)->update([
            'status' => IstTestSubtest::STATUS_ANSWERING,
        ]);
        $this->assertUnavailable($test);

        // The database column is NOT NULL, so exercise the service consistency
        // guard with a loaded model containing the otherwise-unpersistable state.
        $loaded = $this->completedTest('Null percentage')->load('subtests');
        $loaded->subtests->first()->setAttribute('percentage', null);
        $method = new ReflectionMethod(IstResultService::class, 'assertResultIsConsistent');

        $this->expectException(IstResultUnavailableException::class);
        $method->invoke($this->service, $loaded);
    }

    private function completedTest(string $participant = 'Result'): IstTest
    {
        $creation = $this->lifecycle->create([
            'participant_name' => $participant,
            'age' => 32,
            'gender' => 'P',
        ]);
        $creation->takeRawAccessToken();
        $test = $creation->test;
        $test->update([
            'status' => IstTest::STATUS_COMPLETED,
            'started_at' => $this->startedAt,
            'finished_at' => $this->finishedAt,
            'current_subtest_sequence' => 9,
            'total_internal_score' => 61.25,
        ]);

        foreach ($test->subtests()->get() as $runtime) {
            $runtime->update([
                'status' => $runtime->sequence % 2 === 0
                    ? IstTestSubtest::STATUS_TIMED_OUT
                    : IstTestSubtest::STATUS_COMPLETED,
                'locked_at' => $this->finishedAt,
                'finalized_reason' => $runtime->sequence % 2 === 0
                    ? IstTestSubtest::FINALIZED_TIMEOUT
                    : IstTestSubtest::FINALIZED_SUBMITTED,
                'awarded_score' => $runtime->sequence,
                'max_score' => 10,
                'correct_count' => $runtime->sequence,
                'partial_count' => 0,
                'wrong_count' => 0,
                'blank_count' => max(0, 10 - $runtime->sequence),
                'percentage' => $runtime->sequence * 10,
            ]);
        }

        return $test->fresh();
    }

    private function assertUnavailable(IstTest $test): void
    {
        try {
            $this->service->build($test);
            $this->fail('An inconsistent result was accepted.');
        } catch (IstResultUnavailableException) {
            $this->addToAssertionCount(1);
        }
    }

    private function allKeys(array $payload): array
    {
        $keys = [];

        foreach ($payload as $key => $value) {
            if (is_string($key)) {
                $keys[] = $key;
            }

            if (is_array($value)) {
                $keys = [...$keys, ...$this->allKeys($value)];
            }
        }

        return $keys;
    }
}
