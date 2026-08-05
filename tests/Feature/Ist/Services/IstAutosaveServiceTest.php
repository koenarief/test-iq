<?php

namespace Tests\Feature\Ist\Services;

use App\Exceptions\Ist\InvalidIstAutosaveException;
use App\Exceptions\Ist\IstAnswerRevisionConflictException;
use App\Models\Ist\IstAnswer;
use App\Models\Ist\IstTest;
use App\Models\Ist\IstTestQuestion;
use App\Models\Ist\IstTestSubtest;
use App\Services\Ist\IstAutosaveService;
use App\Services\Ist\IstTestLifecycleService;
use App\Support\Ist\IstAnswerType;
use Carbon\CarbonImmutable;

class IstAutosaveServiceTest extends IstDatabaseTestCase
{
    private IstAutosaveService $service;

    private IstTestLifecycleService $lifecycle;

    private CarbonImmutable $now;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(IstAutosaveService::class);
        $this->lifecycle = app(IstTestLifecycleService::class);
        $this->now = CarbonImmutable::parse('2026-08-05 10:00:00', 'UTC');
    }

    public function test_choice_and_numeric_answers_are_saved_without_runtime_scoring(): void
    {
        [$runtime, $choice, $numeric] = $this->activeRuntimeWithChoiceAndNumeric();

        $result = $this->service->save($runtime, [
            $this->numericChange($numeric, '10.00', 1),
            $this->choiceChange($choice, 'B', 1),
        ], $this->now);

        $this->assertSame([$choice->id, $numeric->id], $result->savedQuestionIds);
        $this->assertSame(60, $result->remainingSeconds);
        $this->assertDatabaseHas('ist_answers', [
            'ist_test_question_id' => $choice->id,
            'selected_option_key' => 'B',
            'awarded_score' => null,
            'outcome' => null,
        ]);
        $this->assertDatabaseHas('ist_answers', [
            'ist_test_question_id' => $numeric->id,
            'numeric_answer' => '10.000000',
            'awarded_score' => null,
            'outcome' => null,
        ]);
        $this->assertNotNull($runtime->fresh()->last_autosaved_at);
    }

    public function test_foreign_fields_and_duplicate_question_ids_are_rejected(): void
    {
        [$runtime, $choice] = $this->activeRuntimeWithChoiceAndNumeric();

        try {
            $this->service->save($runtime, [[
                ...$this->choiceChange($choice, 'B', 1),
                'awarded_score' => 1,
            ]], $this->now);
            $this->fail('A client scoring field was accepted.');
        } catch (InvalidIstAutosaveException) {
            $this->assertDatabaseCount('ist_answers', 0);
        }

        $this->expectException(InvalidIstAutosaveException::class);
        $this->service->save($runtime, [
            $this->choiceChange($choice, 'A', 1),
            $this->choiceChange($choice, 'B', 2),
        ], $this->now);
    }

    public function test_invalid_item_rolls_back_the_whole_batch(): void
    {
        [$runtime, $choice, $numeric] = $this->activeRuntimeWithChoiceAndNumeric();

        try {
            $this->service->save($runtime, [
                $this->choiceChange($choice, 'B', 1),
                $this->choiceChange($numeric, 'A', 1),
            ], $this->now);
            $this->fail('An invalid mixed batch was accepted.');
        } catch (InvalidIstAutosaveException) {
            $this->assertDatabaseCount('ist_answers', 0);
            $this->assertNull($runtime->fresh()->last_autosaved_at);
        }
    }

    public function test_wrong_runtime_unknown_option_and_wrong_answer_type_are_rejected(): void
    {
        [$runtime, $choice, $numeric] = $this->activeRuntimeWithChoiceAndNumeric();
        [, $foreignChoice] = $this->activeRuntimeWithChoiceAndNumeric('Foreign');

        foreach ([
            [$this->choiceChange($foreignChoice, 'A', 1)],
            [$this->choiceChange($choice, 'Z', 1)],
            [$this->numericChange($choice, '10', 1)],
            [$this->choiceChange($numeric, 'A', 1)],
        ] as $batch) {
            try {
                $this->service->save($runtime, $batch, $this->now);
                $this->fail('An invalid answer target was accepted.');
            } catch (InvalidIstAutosaveException) {
                $this->assertDatabaseCount('ist_answers', 0);
            }
        }
    }

    public function test_revision_rules_cover_update_stale_idempotent_and_conflict(): void
    {
        [$runtime, $choice] = $this->activeRuntimeWithChoiceAndNumeric();

        $this->service->save($runtime, [$this->choiceChange($choice, 'B', 3)], $this->now);
        $stale = $this->service->save(
            $runtime,
            [$this->choiceChange($choice, 'A', 2)],
            $this->now->addSecond(),
        );

        $this->assertSame([$choice->id], $stale->ignoredStaleQuestionIds);
        $this->assertSame('B', IstAnswer::where('ist_test_question_id', $choice->id)->value('selected_option_key'));

        $idempotent = $this->service->save(
            $runtime,
            [$this->choiceChange($choice, 'B', 3)],
            $this->now->addSeconds(2),
        );
        $this->assertSame([$choice->id], $idempotent->idempotentQuestionIds);

        try {
            $this->service->save(
                $runtime,
                [$this->choiceChange($choice, 'A', 3)],
                $this->now->addSeconds(3),
            );
            $this->fail('A same-revision payload conflict was accepted.');
        } catch (IstAnswerRevisionConflictException) {
            $this->assertSame('B', IstAnswer::where('ist_test_question_id', $choice->id)->value('selected_option_key'));
        }

        $updated = $this->service->save(
            $runtime,
            [$this->choiceChange($choice, 'A', 4)],
            $this->now->addSeconds(4),
        );
        $this->assertSame([$choice->id], $updated->savedQuestionIds);
        $this->assertSame('A', IstAnswer::where('ist_test_question_id', $choice->id)->value('selected_option_key'));
    }

    public function test_numeric_same_revision_uses_canonical_payload_comparison(): void
    {
        [$runtime, , $numeric] = $this->activeRuntimeWithChoiceAndNumeric();
        $this->service->save($runtime, [$this->numericChange($numeric, '10', 1)], $this->now);

        $result = $this->service->save(
            $runtime,
            [$this->numericChange($numeric, '10.00', 1)],
            $this->now->addSecond(),
        );

        $this->assertSame([$numeric->id], $result->idempotentQuestionIds);
    }

    public function test_answer_can_be_cleared_with_a_newer_revision(): void
    {
        [$runtime, $choice] = $this->activeRuntimeWithChoiceAndNumeric();
        $this->service->save($runtime, [$this->choiceChange($choice, 'B', 1)], $this->now);

        $this->service->save(
            $runtime,
            $this->batch($this->choiceChange($choice, null, 2)),
            $this->now->addSecond(),
        );

        $answer = IstAnswer::where('ist_test_question_id', $choice->id)->firstOrFail();
        $this->assertNull($answer->selected_option_key);
        $this->assertSame(2, $answer->client_revision);
    }

    public function test_exact_deadline_and_locked_runtime_are_rejected(): void
    {
        [$runtime, $choice] = $this->activeRuntimeWithChoiceAndNumeric();

        try {
            $this->service->save(
                $runtime,
                [$this->choiceChange($choice, 'B', 1)],
                $this->now->addSeconds(60),
            );
            $this->fail('Autosave at the exact deadline was accepted.');
        } catch (InvalidIstAutosaveException) {
            $this->assertDatabaseCount('ist_answers', 0);
        }

        $runtime->update(['locked_at' => $this->now]);

        $this->expectException(InvalidIstAutosaveException::class);
        $this->service->save($runtime, [$this->choiceChange($choice, 'B', 1)], $this->now);
    }

    private function activeRuntimeWithChoiceAndNumeric(string $participant = 'Autosave'): array
    {
        $creation = $this->lifecycle->create([
            'participant_name' => $participant,
            'age' => 25,
            'gender' => 'L',
        ]);
        $creation->takeRawAccessToken();
        $test = $creation->test;
        $runtime = $test->subtests()->where('sequence', 1)->firstOrFail();
        $test->update([
            'status' => IstTest::STATUS_IN_PROGRESS,
            'started_at' => $this->now->subHour(),
            'current_subtest_sequence' => 1,
        ]);
        $runtime->update([
            'status' => IstTestSubtest::STATUS_ANSWERING,
            'question_count' => 2,
            'started_at' => $this->now->subMinute(),
            'answering_started_at' => $this->now->subMinute(),
            'answering_ends_at' => $this->now->addMinute(),
        ]);

        $choice = $this->createChoiceQuestion($runtime, 1);
        $numeric = IstTestQuestion::create([
            'ist_test_subtest_id' => $runtime->id,
            'source_question_id' => null,
            'display_order' => 2,
            'answer_type' => IstAnswerType::NUMERIC,
            'question_snapshot' => ['prompt' => 'Numeric'],
            'options_snapshot' => [],
            'answer_key_snapshot' => ['numeric_answer' => '10.000000'],
            'max_score' => 1,
        ]);

        return [$runtime->fresh(), $choice, $numeric];
    }

    private function createChoiceQuestion(IstTestSubtest $runtime, int $order): IstTestQuestion
    {
        return IstTestQuestion::create([
            'ist_test_subtest_id' => $runtime->id,
            'source_question_id' => null,
            'display_order' => $order,
            'answer_type' => IstAnswerType::SINGLE_CHOICE,
            'question_snapshot' => ['prompt' => 'Choice'],
            'options_snapshot' => array_map(
                static fn (string $key, int $index): array => [
                    'option_key' => $key,
                    'display_order' => $index + 1,
                ],
                ['A', 'B', 'C', 'D', 'E'],
                array_keys(['A', 'B', 'C', 'D', 'E']),
            ),
            'answer_key_snapshot' => ['correct_option_key' => 'B'],
            'max_score' => 1,
        ]);
    }

    private function choiceChange(IstTestQuestion $question, ?string $option, int $revision): array
    {
        return [
            'ist_test_question_id' => $question->id,
            'selected_option_key' => $option,
            'client_revision' => $revision,
        ];
    }

    private function numericChange(IstTestQuestion $question, ?string $answer, int $revision): array
    {
        return [
            'ist_test_question_id' => $question->id,
            'numeric_answer' => $answer,
            'client_revision' => $revision,
        ];
    }

    private function batch(array $change): array
    {
        return [$change];
    }
}
