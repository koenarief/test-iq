<?php

namespace App\Services\Ist;

use App\Exceptions\Ist\InvalidIstMeExampleCompletionException;
use App\Models\Ist\IstQuestion;
use App\Models\Ist\IstTestSubtest;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

final class IstMeExampleCompletionService
{
    /**
     * For ME only, the otherwise-unused instruction_viewed_at field records
     * explicit example confirmation. It is never a scored answer or timer.
     */
    public function complete(
        IstTestSubtest $testSubtest,
        string $selectedOptionKey,
        CarbonInterface $now,
    ): IstTestSubtest {
        if (! $testSubtest->exists || ! $testSubtest->getKey()) {
            throw new InvalidIstMeExampleCompletionException(0, 'runtime subtest does not exist');
        }

        return DB::transaction(function () use ($testSubtest, $selectedOptionKey, $now): IstTestSubtest {
            $runtime = IstTestSubtest::query()
                ->with(['subtest', 'test'])
                ->lockForUpdate()
                ->find($testSubtest->getKey());

            if (! $runtime || ! $runtime->subtest || ! $runtime->test) {
                throw new InvalidIstMeExampleCompletionException(
                    (int) $testSubtest->getKey(),
                    'runtime relation is unavailable',
                );
            }

            if ($runtime->subtest->code !== 'ME') {
                throw new InvalidIstMeExampleCompletionException($runtime->id, 'subtest is not ME');
            }

            if ($runtime->sequence !== $runtime->test->current_subtest_sequence
                || $runtime->status !== IstTestSubtest::STATUS_INSTRUCTION
                || $runtime->started_at !== null
                || $runtime->locked_at !== null) {
                throw new InvalidIstMeExampleCompletionException(
                    $runtime->id,
                    'runtime is not an open current instruction',
                );
            }

            $hasSelectedExampleOption = IstQuestion::query()
                ->where('ist_subtest_id', $runtime->ist_subtest_id)
                ->where('kind', IstQuestion::KIND_EXAMPLE)
                ->where('is_active', true)
                ->whereHas('options', fn ($query) => $query
                    ->where('option_key', $selectedOptionKey)
                    ->where('is_active', true))
                ->exists();

            if (! $hasSelectedExampleOption) {
                throw new InvalidIstMeExampleCompletionException(
                    $runtime->id,
                    'selected example option is unavailable',
                );
            }

            if ($runtime->instruction_viewed_at === null) {
                $runtime->update([
                    'instruction_viewed_at' => CarbonImmutable::instance($now),
                ]);
            }

            return $runtime->fresh(['subtest', 'test']);
        });
    }

    public function isCompleted(IstTestSubtest $testSubtest): bool
    {
        return $testSubtest->subtest?->code === 'ME'
            && $testSubtest->instruction_viewed_at !== null;
    }
}
