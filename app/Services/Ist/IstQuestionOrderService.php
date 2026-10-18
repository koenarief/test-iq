<?php

namespace App\Services\Ist;

use App\Models\Ist\IstQuestion;
use App\Models\Ist\IstSubtest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class IstQuestionOrderService
{
    // Call inside the same transaction as question/options/count mutations.
    // Lock the small subtest catalog in a fixed order, including empty groups.
    public function lock(): void
    {
        if (DB::transactionLevel() === 0) {
            throw new \LogicException('Question ordering requires a transaction.');
        }
        IstSubtest::query()->orderBy('id')->lockForUpdate()->get(['id']);
    }

    public function save(array $attributes, ?IstQuestion $question = null): IstQuestion
    {
        $this->lock();
        $position = (int) $attributes['question_number'];
        $previous = null;
        if ($question) {
            $question = IstQuestion::query()->findOrFail($question->id);
            $previous = [$question->ist_subtest_id, $question->kind];
        }

        $group = [(int) $attributes['ist_subtest_id'], $attributes['kind']];
        $rows = $this->questions(...$group);
        // Editing only content in a legacy sparse bank must preserve relative order.
        if ($question && $previous === $group && $position === $question->question_number) {
            $position = $rows->search(fn ($row) => $row->id === $question->id) + 1;
        }
        $others = $rows->reject(fn ($row) => $row->id === $question?->id)->values();
        $position = min($position, $others->count() + 1);

        // Reuse deleted numbers, but never overwrite an existing question.
        if (! $question || $previous !== $group) {
            $used = $others->pluck('question_number')->flip();
            $free = 1;
            while ($used->has($free)) {
                $free++;
            }
            if ($free > 65535) {
                throw ValidationException::withMessages(['question_number' => 'Jumlah soal sudah mencapai batas.']);
            }
            $attributes['question_number'] = $free;
        } else {
            $attributes['question_number'] = $question->question_number;
        }
        $attributes['display_order'] = $position;
        $question ??= new IstQuestion;
        $question->fill($attributes)->save();
        $others->splice($position - 1, 0, [$question]);
        $this->resequence($others);
        if ($previous && $previous !== $group) {
            $this->normalize(...$previous);
        }

        return $question->refresh();
    }

    public function normalize(int $subtestId, string $kind): void
    {
        $this->resequence($this->questions($subtestId, $kind));
    }

    private function questions(int $subtestId, string $kind): Collection
    {
        return IstQuestion::query()->where('ist_subtest_id', $subtestId)
            ->where('kind', $kind)->orderBy('question_number')->orderBy('id')->get();
    }

    private function resequence(Collection $questions): void
    {
        $byNumber = $questions->keyBy('question_number')->all();
        foreach ($questions->values() as $index => $question) {
            $target = $index + 1;
            $old = $question->question_number;
            if ($old !== $target) {
                $occupant = $byNumber[$target] ?? null;
                // Zero is reserved only within this transaction, allowing swaps
                // without overflowing the unsigned smallint or violating uniqueness.
                if ($occupant) {
                    DB::table('ist_questions')->where('id', $occupant->id)->update(['question_number' => 0]);
                }
                $question->update(['question_number' => $target]);
                unset($byNumber[$old]);
                if ($occupant) {
                    $occupant->update(['question_number' => $old]);
                    $byNumber[$old] = $occupant;
                }
                $byNumber[$target] = $question;
            }
            $question->update(['display_order' => $target]);
        }
    }
}
