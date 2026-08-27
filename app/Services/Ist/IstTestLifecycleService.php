<?php

namespace App\Services\Ist;

use App\Data\Ist\IstTestCreationResult;
use App\Exceptions\Ist\InvalidIstCatalogException;
use App\Models\Ist\IstSubtest;
use App\Models\Ist\IstTest;
use App\Models\Ist\IstTestSubtest;
use App\Support\Ist\IstSubtestCatalog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class IstTestLifecycleService
{
    private const MINIMUM_AGE = 10;

    private const MAXIMUM_AGE = 100;

    private const ALLOWED_GENDERS = ['L', 'P'];

    public function create(array $participantData, ?int $merchantId = null): IstTestCreationResult
    {
        $validated = $this->validateParticipant($participantData);

        return DB::transaction(function () use ($validated, $merchantId): IstTestCreationResult {
            $subtests = IstSubtest::query()
                ->where('is_active', true)
                ->orderBy('sequence')
                ->sharedLock()
                ->get();

            $this->assertValidCatalog($subtests);

            $rawAccessToken = bin2hex(random_bytes(32));

            $test = IstTest::create([
                'public_id' => (string) Str::uuid(),
                'access_token_hash' => hash('sha256', $rawAccessToken),
                'merchant_id' => $merchantId,
                'participant_name' => $validated['participant_name'],
                'age' => $validated['age'],
                'gender' => $validated['gender'],
                'status' => IstTest::STATUS_DRAFT,
                'current_subtest_sequence' => 1,
                'started_at' => null,
                'finished_at' => null,
                'total_internal_score' => null,
            ]);

            foreach ($subtests as $subtest) {
                $test->subtests()->create([
                    'ist_subtest_id' => $subtest->id,
                    'sequence' => $subtest->sequence,
                    'status' => $subtest->sequence === 1
                        ? IstTestSubtest::STATUS_INSTRUCTION
                        : IstTestSubtest::STATUS_PENDING,
                    'question_count' => $subtest->question_count,
                ]);
            }

            return new IstTestCreationResult($test->fresh(), $rawAccessToken);
        });
    }

    private function validateParticipant(array $participantData): array
    {
        if (array_key_exists('participant_name', $participantData)
            && is_string($participantData['participant_name'])) {
            $participantData['participant_name'] = trim($participantData['participant_name']);
        }

        return Validator::make($participantData, [
            'participant_name' => ['required', 'string', 'max:255'],
            'age' => ['required', 'integer', 'between:'.self::MINIMUM_AGE.','.self::MAXIMUM_AGE],
            'gender' => ['required', Rule::in(self::ALLOWED_GENDERS)],
        ])->validate();
    }

    private function assertValidCatalog(Collection $subtests): void
    {
        if ($subtests->count() !== IstSubtestCatalog::EXPECTED_SUBTEST_COUNT) {
            throw new InvalidIstCatalogException('active subtest count must be exactly 9');
        }

        $expectedCodes = array_column(IstSubtestCatalog::all(), 'code');
        $actualCodes = $subtests->pluck('code')->values()->all();

        if ($actualCodes !== $expectedCodes) {
            throw new InvalidIstCatalogException('active subtest codes or order do not match SE through ME');
        }

        if ($subtests->pluck('sequence')->values()->all() !== range(1, 9)) {
            throw new InvalidIstCatalogException('active subtest sequences must be 1 through 9');
        }

        if ($subtests->sum('question_count') !== IstSubtestCatalog::EXPECTED_QUESTION_COUNT) {
            throw new InvalidIstCatalogException('active subtest question total must be 104');
        }

        if ($subtests->sum('duration_seconds') !== IstSubtestCatalog::EXPECTED_CORE_DURATION_SECONDS) {
            throw new InvalidIstCatalogException('active subtest duration total must be 2700 seconds');
        }
    }
}
