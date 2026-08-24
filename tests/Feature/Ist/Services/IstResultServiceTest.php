<?php

namespace Tests\Feature\Ist\Services;

use App\Exceptions\Ist\IstResultUnavailableException;
use App\Models\IstNormSubtest;
use App\Models\IstNormTotal;
use App\Models\Ist\IstSubtest;
use App\Models\Ist\IstTest;
use App\Models\Ist\IstTestSubtest;
use App\Services\Ist\IstResultService;
use App\Services\Ist\IstTestLifecycleService;
use App\Support\Ist\IstAnswerType;
use Carbon\CarbonImmutable;

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
        $this->seedNorms($test->age);
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
        $this->assertSame(['code' => 'SE', 'standardScore' => 91], $result->graphPoints[0]);
        $this->assertSame(1, $result->subtests[0]->rawScore);
        $this->assertSame(91, $result->subtests[0]->standardScore);
        $this->assertSame(9, $result->subtests[8]->rawScore);
        $this->assertSame(99, $result->subtests[8]->standardScore);

        $this->assertSame(45, $result->totalRawScore);
        $this->assertSame(855, $result->totalStandardScore);
        $this->assertSame(97, $result->iqScore);
        $this->assertSame('Rata-rata', $result->iqCategory);
        // verbal (SE+WA+AN+GE=370) vs spatial (FA+WU+ZR+RA=386): diff -16.
        $this->assertSame('M-Dominant (Spatial High)', $result->dominanceProfile);

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

    public function test_iq_gracefully_degrades_to_null_when_norm_data_is_missing(): void
    {
        $test = $this->completedTest();

        $result = $this->service->build($test);

        $this->assertSame(45, $result->totalRawScore);
        $this->assertNull($result->totalStandardScore);
        $this->assertNull($result->iqScore);
        $this->assertNull($result->iqCategory);
        $this->assertNull($result->dominanceProfile);
        $this->assertNull($result->subtests[0]->standardScore);
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

    public function test_non_final_runtime_is_rejected(): void
    {
        $test = $this->completedTest();
        $test->subtests()->where('sequence', 5)->update([
            'status' => IstTestSubtest::STATUS_ANSWERING,
        ]);
        $this->assertUnavailable($test);
    }

    private function seedNorms(int $age): void
    {
        $codes = ['SE', 'WA', 'AN', 'GE', 'RA', 'ZR', 'FA', 'WU', 'ME'];

        foreach ($codes as $sequence => $code) {
            $rawScore = $sequence + 1;

            IstNormSubtest::create([
                'subtest' => $code,
                'raw_score' => $rawScore,
                'standard_score' => 90 + $rawScore,
                'min_age' => $age - 5,
                'max_age' => $age + 5,
            ]);
        }

        IstNormTotal::create([
            'total_sw' => 855,
            'iq_score' => 97,
            'iq_category' => 'Rata-rata',
            'min_age' => $age - 5,
            'max_age' => $age + 5,
        ]);
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
