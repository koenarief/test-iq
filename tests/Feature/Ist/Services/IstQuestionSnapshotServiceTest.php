<?php

namespace Tests\Feature\Ist\Services;

use App\Exceptions\Ist\IncompleteIstSnapshotException;
use App\Exceptions\Ist\InvalidIstQuestionCountException;
use App\Exceptions\Ist\InvalidIstQuestionDefinitionException;
use App\Models\Ist\IstAnswer;
use App\Models\Ist\IstQuestion;
use App\Models\Ist\IstQuestionOption;
use App\Models\Ist\IstTestQuestion;
use App\Models\Ist\IstTestSubtest;
use App\Services\Ist\IstQuestionSnapshotService;
use App\Services\Ist\IstTestLifecycleService;
use App\Support\Ist\IstAnswerType;

class IstQuestionSnapshotServiceTest extends IstDatabaseTestCase
{
    private IstQuestionSnapshotService $snapshotService;

    private IstTestLifecycleService $lifecycleService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->snapshotService = app(IstQuestionSnapshotService::class);
        $this->lifecycleService = app(IstTestLifecycleService::class);
    }

    public function test_it_rejects_fewer_active_questions_than_the_runtime_snapshot_count(): void
    {
        $runtime = $this->runtime('SE', 2);
        $question = $this->question($runtime, 1);
        $this->binaryOptions($question);

        $this->expectException(InvalidIstQuestionCountException::class);
        $this->snapshotService->snapshot($runtime);
    }

    public function test_it_rejects_more_active_questions_than_the_runtime_snapshot_count(): void
    {
        $runtime = $this->runtime('SE', 1);

        foreach ([1, 2] as $number) {
            $question = $this->question($runtime, $number);
            $this->binaryOptions($question);
        }

        $this->expectException(InvalidIstQuestionCountException::class);
        $this->snapshotService->snapshot($runtime);
    }

    public function test_it_creates_safe_ordered_binary_snapshots_without_answers(): void
    {
        $runtime = $this->runtime('SE', 2);
        $later = $this->question($runtime, 2, ['display_order' => 20]);
        $this->binaryOptions($later);
        $earlier = $this->question($runtime, 1, [
            'display_order' => 10,
            'image_disk' => 'public',
            'image_path' => 'ist/questions/se-1.png',
            'image_alt' => 'Gambar soal SE 1',
        ]);
        $this->binaryOptions($earlier, imageOption: true);

        $snapshots = $this->snapshotService->snapshot($runtime);

        $this->assertCount(2, $snapshots);
        $this->assertSame([10, 20], $snapshots->pluck('display_order')->all());
        $this->assertSame([$earlier->id, $later->id], $snapshots->pluck('source_question_id')->all());

        $first = $snapshots->first();
        $this->assertSame('ist/questions/se-1.png', $first->question_snapshot['image_path']);
        $this->assertSame('Gambar soal SE 1', $first->question_snapshot['image_alt']);
        $this->assertSame('B', $first->answer_key_snapshot['correct_option_key']);
        $this->assertSame(['A' => 0, 'B' => 1, 'C' => 0, 'D' => 0, 'E' => 0], $first->answer_key_snapshot['scores']);
        $this->assertSame('ist/options/se-1-a.png', $first->options_snapshot[0]['image_path']);

        foreach ($first->options_snapshot as $option) {
            $this->assertArrayNotHasKey('is_correct', $option);
            $this->assertArrayNotHasKey('score_value', $option);
            $this->assertArrayNotHasKey('outcome', $option);
        }

        $this->assertArrayNotHasKey('answer_key_snapshot', $first->toArray());
        $this->assertStringNotContainsString('answer_key_snapshot', $first->toJson());
        $this->assertSame(0, IstAnswer::query()->count());
    }

    public function test_examples_and_soft_deleted_questions_are_not_snapshotted(): void
    {
        $runtime = $this->runtime('SE', 1);
        $scored = $this->question($runtime, 1);
        $this->binaryOptions($scored);

        $this->question($runtime, 2, [
            'kind' => IstQuestion::KIND_EXAMPLE,
            'display_order' => 2,
        ]);

        $deleted = $this->question($runtime, 3, ['display_order' => 3]);
        $this->binaryOptions($deleted);
        $deleted->delete();

        $snapshots = $this->snapshotService->snapshot($runtime);

        $this->assertCount(1, $snapshots);
        $this->assertSame($scored->id, $snapshots->first()->source_question_id);
    }

    public function test_weighted_ge_snapshot_contains_scores_and_outcomes(): void
    {
        $runtime = $this->runtime('GE', 1);
        $question = $this->question($runtime, 1, [
            'answer_type' => IstAnswerType::SINGLE_CHOICE_WEIGHTED,
            'max_score' => 4,
        ]);
        $this->weightedOptions($question, [0, 2, 4, 1, 3], 2);

        $snapshot = $this->snapshotService->snapshot($runtime)->first();

        $this->assertSame(
            ['A' => 0, 'B' => 2, 'C' => 4, 'D' => 1, 'E' => 3],
            $snapshot->answer_key_snapshot['scores'],
        );
        $this->assertSame(
            ['A' => 'wrong', 'B' => 'partial', 'C' => 'correct', 'D' => 'partial', 'E' => 'partial'],
            $snapshot->answer_key_snapshot['outcomes'],
        );
    }

    public function test_numeric_snapshot_is_canonical_and_does_not_depend_on_options(): void
    {
        $runtime = $this->runtime('RA', 1);
        $question = $this->question($runtime, 1, [
            'answer_type' => IstAnswerType::NUMERIC,
            'numeric_answer_key' => '10.0',
        ]);

        $snapshot = $this->snapshotService->snapshot($runtime)->first();

        $this->assertSame([], $snapshot->options_snapshot);
        $this->assertSame('10.000000', $snapshot->answer_key_snapshot['numeric_answer']);
    }

    public function test_repeated_snapshot_call_is_idempotent(): void
    {
        $runtime = $this->runtime('SE', 1);
        $question = $this->question($runtime, 1);
        $this->binaryOptions($question);

        $first = $this->snapshotService->snapshot($runtime);
        $second = $this->snapshotService->snapshot($runtime);

        $this->assertSame($first->pluck('id')->all(), $second->pluck('id')->all());
        $this->assertSame(1, IstTestQuestion::query()->where('ist_test_subtest_id', $runtime->id)->count());
    }

    public function test_partial_existing_snapshot_is_rejected_without_adding_records(): void
    {
        $runtime = $this->runtime('SE', 2);
        $question = $this->question($runtime, 1);

        IstTestQuestion::create([
            'ist_test_subtest_id' => $runtime->id,
            'source_question_id' => $question->id,
            'display_order' => 1,
            'answer_type' => IstAnswerType::SINGLE_CHOICE,
            'question_snapshot' => ['prompt' => 'partial'],
            'options_snapshot' => [],
            'answer_key_snapshot' => ['scores' => []],
            'max_score' => 1,
        ]);

        try {
            $this->snapshotService->snapshot($runtime);
            $this->fail('Partial snapshot was accepted.');
        } catch (IncompleteIstSnapshotException) {
            $this->assertSame(1, IstTestQuestion::query()->where('ist_test_subtest_id', $runtime->id)->count());
        }
    }

    public function test_runtime_question_count_remains_the_source_of_truth(): void
    {
        $runtime = $this->runtime('SE', 1);
        $runtime->subtest()->update(['question_count' => 99]);
        $question = $this->question($runtime, 1);
        $this->binaryOptions($question);

        $snapshots = $this->snapshotService->snapshot($runtime);

        $this->assertCount(1, $snapshots);
    }

    public function test_choice_question_with_fewer_than_five_options_is_rejected(): void
    {
        $runtime = $this->runtime('SE', 1);
        $question = $this->question($runtime, 1);
        $this->binaryOptions($question, optionCount: 4);

        $this->expectException(InvalidIstQuestionDefinitionException::class);
        $this->snapshotService->snapshot($runtime);
    }

    public function test_choice_question_with_more_than_five_options_is_rejected(): void
    {
        $runtime = $this->runtime('SE', 1);
        $question = $this->question($runtime, 1);
        $this->binaryOptions($question, optionCount: 6);

        $this->expectException(InvalidIstQuestionDefinitionException::class);
        $this->snapshotService->snapshot($runtime);
    }

    public function test_binary_question_with_two_correct_options_is_rejected(): void
    {
        $runtime = $this->runtime('SE', 1);
        $question = $this->question($runtime, 1);
        $this->binaryOptions($question, correctIndexes: [1, 2]);

        $this->expectException(InvalidIstQuestionDefinitionException::class);
        $this->snapshotService->snapshot($runtime);
    }

    public function test_binary_question_without_a_correct_option_is_rejected(): void
    {
        $runtime = $this->runtime('SE', 1);
        $question = $this->question($runtime, 1);
        $this->binaryOptions($question, correctIndexes: []);

        $this->expectException(InvalidIstQuestionDefinitionException::class);
        $this->snapshotService->snapshot($runtime);
    }

    public function test_binary_score_other_than_zero_or_one_is_rejected(): void
    {
        $runtime = $this->runtime('SE', 1);
        $question = $this->question($runtime, 1);
        $this->binaryOptions($question, scoreOverrides: [3 => 2]);

        $this->expectException(InvalidIstQuestionDefinitionException::class);
        $this->snapshotService->snapshot($runtime);
    }

    public function test_ge_without_score_four_is_rejected(): void
    {
        $runtime = $this->runtime('GE', 1);
        $question = $this->question($runtime, 1, [
            'answer_type' => IstAnswerType::SINGLE_CHOICE_WEIGHTED,
            'max_score' => 4,
        ]);
        $this->weightedOptions($question, [0, 1, 2, 3, 0], null);

        $this->expectException(InvalidIstQuestionDefinitionException::class);
        $this->snapshotService->snapshot($runtime);
    }

    public function test_ge_with_two_score_four_options_is_rejected(): void
    {
        $runtime = $this->runtime('GE', 1);
        $question = $this->question($runtime, 1, [
            'answer_type' => IstAnswerType::SINGLE_CHOICE_WEIGHTED,
            'max_score' => 4,
        ]);
        $this->weightedOptions($question, [4, 4, 2, 1, 0], 0, [1]);

        $this->expectException(InvalidIstQuestionDefinitionException::class);
        $this->snapshotService->snapshot($runtime);
    }

    public function test_ge_with_inconsistent_correct_flag_is_rejected(): void
    {
        $runtime = $this->runtime('GE', 1);
        $question = $this->question($runtime, 1, [
            'answer_type' => IstAnswerType::SINGLE_CHOICE_WEIGHTED,
            'max_score' => 4,
        ]);
        $this->weightedOptions($question, [0, 1, 4, 2, 3], 1);

        $this->expectException(InvalidIstQuestionDefinitionException::class);
        $this->snapshotService->snapshot($runtime);
    }

    public function test_numeric_question_without_an_answer_key_is_rejected(): void
    {
        $runtime = $this->runtime('RA', 1);
        $this->question($runtime, 1, [
            'answer_type' => IstAnswerType::NUMERIC,
            'numeric_answer_key' => null,
        ]);

        $this->expectException(InvalidIstQuestionDefinitionException::class);
        $this->snapshotService->snapshot($runtime);
    }

    private function runtime(string $code, int $questionCount): IstTestSubtest
    {
        $result = $this->lifecycleService->create([
            'participant_name' => "Snapshot {$code}",
            'age' => 25,
            'gender' => 'L',
        ]);
        $result->takeRawAccessToken();

        $runtime = $result->test->subtests()
            ->whereHas('subtest', fn ($query) => $query->where('code', $code))
            ->firstOrFail();
        $runtime->update(['question_count' => $questionCount]);

        return $runtime->fresh('subtest');
    }

    private function question(
        IstTestSubtest $runtime,
        int $number,
        array $overrides = [],
    ): IstQuestion {
        return IstQuestion::create([
            'ist_subtest_id' => $runtime->ist_subtest_id,
            'question_number' => $number,
            'display_order' => $number,
            'kind' => IstQuestion::KIND_SCORED,
            'answer_type' => IstAnswerType::SINGLE_CHOICE,
            'prompt' => "Fixture question {$number}",
            'max_score' => 1,
            'version' => 1,
            'is_active' => true,
            ...$overrides,
        ]);
    }

    private function binaryOptions(
        IstQuestion $question,
        int $optionCount = 5,
        array $correctIndexes = [1],
        array $scoreOverrides = [],
        bool $imageOption = false,
    ): void {
        for ($index = 0; $index < $optionCount; $index++) {
            $isCorrect = in_array($index, $correctIndexes, true);

            IstQuestionOption::create([
                'ist_question_id' => $question->id,
                'option_key' => chr(65 + $index),
                'option_text' => 'Option '.chr(65 + $index),
                'image_disk' => $imageOption ? 'public' : null,
                'image_path' => $imageOption ? 'ist/options/se-1-'.strtolower(chr(65 + $index)).'.png' : null,
                'image_alt' => $imageOption ? 'Option image '.chr(65 + $index) : null,
                'display_order' => $index + 1,
                'is_correct' => $isCorrect,
                'score_value' => $scoreOverrides[$index] ?? ($isCorrect ? 1 : 0),
                'is_active' => true,
            ]);
        }
    }

    private function weightedOptions(
        IstQuestion $question,
        array $scores,
        ?int $correctIndex,
        array $additionalCorrectIndexes = [],
    ): void {
        foreach ($scores as $index => $score) {
            IstQuestionOption::create([
                'ist_question_id' => $question->id,
                'option_key' => chr(65 + $index),
                'option_text' => 'Weighted '.chr(65 + $index),
                'display_order' => $index + 1,
                'is_correct' => $index === $correctIndex
                    || in_array($index, $additionalCorrectIndexes, true),
                'score_value' => $score,
                'is_active' => true,
            ]);
        }
    }
}
