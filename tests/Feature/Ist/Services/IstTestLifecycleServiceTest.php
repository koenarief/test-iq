<?php

namespace Tests\Feature\Ist\Services;

use App\Exceptions\Ist\InvalidIstCatalogException;
use App\Models\Ist\IstAnswer;
use App\Models\Ist\IstSubtest;
use App\Models\Ist\IstTest;
use App\Models\Ist\IstTestQuestion;
use App\Models\Ist\IstTestSubtest;
use App\Services\Ist\IstTestLifecycleService;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;

class IstTestLifecycleServiceTest extends IstDatabaseTestCase
{
    private IstTestLifecycleService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(IstTestLifecycleService::class);
    }

    public function test_it_creates_a_draft_test_and_nine_ordered_runtime_subtests(): void
    {
        $result = $this->service->create($this->validParticipant());
        $test = $result->test->fresh('subtests.subtest');

        $this->assertSame('Peserta IST', $test->participant_name);
        $this->assertSame(25, $test->age);
        $this->assertSame('P', $test->gender);
        $this->assertTrue(Str::isUuid($test->public_id));
        $this->assertSame(IstTest::STATUS_DRAFT, $test->status);
        $this->assertSame(1, $test->current_subtest_sequence);
        $this->assertNull($test->started_at);
        $this->assertNull($test->finished_at);
        $this->assertNull($test->total_internal_score);

        $runtimes = $test->subtests->sortBy('sequence')->values();

        $this->assertCount(9, $runtimes);
        $this->assertSame(range(1, 9), $runtimes->pluck('sequence')->all());
        $this->assertSame(
            ['SE', 'WA', 'AN', 'GE', 'RA', 'ZR', 'FA', 'WU', 'ME'],
            $runtimes->pluck('subtest.code')->all(),
        );
        $this->assertSame(
            [20, 20, 20, 10, 12, 12, 10, 12, 12],
            $runtimes->pluck('question_count')->all(),
        );
        $this->assertSame(IstTestSubtest::STATUS_INSTRUCTION, $runtimes->first()->status);
        $this->assertTrue(
            $runtimes->slice(1)->every(
                fn (IstTestSubtest $runtime) => $runtime->status === IstTestSubtest::STATUS_PENDING
            )
        );

        foreach ($runtimes as $runtime) {
            $this->assertNull($runtime->started_at);
            $this->assertNull($runtime->memorization_started_at);
            $this->assertNull($runtime->memorization_ends_at);
            $this->assertNull($runtime->answering_started_at);
            $this->assertNull($runtime->answering_ends_at);
            $this->assertNull($runtime->locked_at);
        }

        $this->assertSame(0, IstTestQuestion::query()->count());
        $this->assertSame(0, IstAnswer::query()->count());
    }

    public function test_public_ids_are_valid_and_unique(): void
    {
        $first = $this->service->create($this->validParticipant())->test;
        $second = $this->service->create([
            ...$this->validParticipant(),
            'participant_name' => 'Peserta Kedua',
        ])->test;

        $this->assertTrue(Str::isUuid($first->public_id));
        $this->assertTrue(Str::isUuid($second->public_id));
        $this->assertNotSame($first->public_id, $second->public_id);
    }

    public function test_access_token_is_returned_once_hashed_and_hidden_from_serialization(): void
    {
        $result = $this->service->create($this->validParticipant());
        $test = $result->test->fresh();
        $rawToken = $result->takeRawAccessToken();

        $this->assertSame(64, strlen($rawToken));
        $this->assertFalse(hash_equals($rawToken, $test->getRawOriginal('access_token_hash')));
        $this->assertTrue(hash_equals(
            $test->getRawOriginal('access_token_hash'),
            hash('sha256', $rawToken),
        ));
        $this->assertArrayNotHasKey('access_token_hash', $test->toArray());
        $this->assertStringNotContainsString('access_token_hash', $test->toJson());

        unset($rawToken);

        $this->expectException(LogicException::class);
        $result->takeRawAccessToken();
    }

    public function test_invalid_catalog_rolls_back_without_creating_a_test(): void
    {
        IstSubtest::query()->where('code', 'ME')->update(['is_active' => false]);
        $before = IstTest::query()->count();

        try {
            $this->service->create($this->validParticipant());
            $this->fail('Invalid catalog was accepted.');
        } catch (InvalidIstCatalogException) {
            $this->assertSame($before, IstTest::query()->count());
            $this->assertSame(0, IstTestSubtest::query()->count());
        }
    }

    #[DataProvider('invalidParticipantProvider')]
    public function test_invalid_participant_data_does_not_create_records(array $participant): void
    {
        $before = IstTest::query()->count();

        try {
            $this->service->create($participant);
            $this->fail('Invalid participant data was accepted.');
        } catch (ValidationException) {
            $this->assertSame($before, IstTest::query()->count());
            $this->assertSame(0, IstTestSubtest::query()->count());
        }
    }

    public static function invalidParticipantProvider(): array
    {
        return [
            'blank name' => [['participant_name' => '   ', 'age' => 25, 'gender' => 'P']],
            'age below range' => [['participant_name' => 'A', 'age' => 9, 'gender' => 'P']],
            'age above range' => [['participant_name' => 'A', 'age' => 101, 'gender' => 'L']],
            'non integer age' => [['participant_name' => 'A', 'age' => 'dua puluh', 'gender' => 'L']],
            'invalid gender' => [['participant_name' => 'A', 'age' => 25, 'gender' => 'X']],
        ];
    }

    public function test_failure_during_runtime_creation_rolls_back_the_whole_session(): void
    {
        $createdRuntimeCount = 0;

        IstTestSubtest::created(function () use (&$createdRuntimeCount): void {
            $createdRuntimeCount++;

            if ($createdRuntimeCount === 4) {
                throw new RuntimeException('Simulated runtime creation failure.');
            }
        });

        try {
            $this->service->create($this->validParticipant());
            $this->fail('The simulated runtime failure did not occur.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated runtime creation failure.', $exception->getMessage());
            $this->assertSame(0, IstTest::query()->count());
            $this->assertSame(0, IstTestSubtest::query()->count());
        } finally {
            IstTestSubtest::flushEventListeners();
        }
    }

    private function validParticipant(): array
    {
        return [
            'participant_name' => '  Peserta IST  ',
            'age' => 25,
            'gender' => 'P',
        ];
    }
}
