<?php

namespace App\Services\Ist;

use App\Data\Ist\IstSubtestFinalizationResult;
use App\Enums\Ist\IstFinalizationReason;
use App\Enums\Ist\IstSubtestPhase;
use App\Exceptions\Ist\IncompleteIstSnapshotException;
use App\Exceptions\Ist\InvalidIstFinalizationException;
use App\Models\Ist\IstAnswer;
use App\Models\Ist\IstTest;
use App\Models\Ist\IstTestQuestion;
use App\Models\Ist\IstTestSubtest;
use App\Support\Ist\IstAnswerType;
use App\Support\Ist\IstScoreCalculator;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

final class IstSubtestFinalizationService
{
    /**
     * Per-item scoring is intentionally flat (1 point, or 0-3 for GE) so the
     * resulting raw score can be looked up directly against IST age norm
     * tables. Difficulty weighting no longer multiplies the point value.
     */
    private const NEUTRAL_DIFFICULTY = 'easy';

    public function __construct(
        private readonly IstAutosaveService $autosave,
        private readonly IstTimerService $timer,
        private readonly IstScoreCalculator $calculator,
    ) {}

    public function finalize(
        IstTestSubtest $testSubtest,
        IstFinalizationReason $reason,
        CarbonInterface $now,
        array $finalAnswers = [],
    ): IstSubtestFinalizationResult {
        if (! $testSubtest->exists || ! $testSubtest->getKey()) {
            throw new InvalidIstFinalizationException(0, 'runtime subtest does not exist');
        }

        return DB::transaction(function () use (
            $testSubtest,
            $reason,
            $now,
            $finalAnswers,
        ): IstSubtestFinalizationResult {
            // Keep this lock order aligned with start/autosave: runtime, parent,
            // optional next runtime, then answers ordered by question ID.
            $runtime = IstTestSubtest::query()
                ->with('subtest')
                ->lockForUpdate()
                ->find($testSubtest->getKey());

            if (! $runtime || ! $runtime->subtest) {
                throw new InvalidIstFinalizationException(
                    (int) $testSubtest->getKey(),
                    'runtime subtest is unavailable',
                );
            }

            $test = IstTest::query()
                ->lockForUpdate()
                ->find($runtime->ist_test_id);

            if (! $test) {
                throw new InvalidIstFinalizationException($runtime->id, 'parent test is unavailable');
            }

            if ($this->isTerminal($runtime)) {
                return $this->resultFromPersisted($runtime, $test, true);
            }

            $this->assertCanFinalize($runtime, $test, $reason, $now, $finalAnswers);

            $questions = $runtime->testQuestions()
                ->orderBy('id')
                ->get();

            if ($questions->count() !== $runtime->question_count) {
                throw new IncompleteIstSnapshotException(
                    $runtime->id,
                    $runtime->question_count,
                    $questions->count(),
                );
            }

            $nextRuntime = null;

            if ($runtime->sequence < 9) {
                $nextRuntime = IstTestSubtest::query()
                    ->where('ist_test_id', $test->id)
                    ->where('sequence', $runtime->sequence + 1)
                    ->lockForUpdate()
                    ->first();

                if (! $nextRuntime || $nextRuntime->status !== IstTestSubtest::STATUS_PENDING) {
                    throw new InvalidIstFinalizationException(
                        $runtime->id,
                        'next runtime subtest is unavailable or not pending',
                    );
                }
            }

            if ($reason === IstFinalizationReason::SUBMITTED && $finalAnswers !== []) {
                // This is deliberately the same revision-protected autosave path.
                // The outer transaction already holds runtime, parent, and next locks.
                $this->autosave->save($runtime, $finalAnswers, $now);
            }

            $statistics = $this->scoreAnswers($runtime, $questions, $now);
            $percentage = $this->calculator->percentage(
                $statistics['awarded_score'],
                $statistics['max_score'],
            );
            $lockedAt = CarbonImmutable::instance($now);

            $runtime->update([
                'status' => $reason === IstFinalizationReason::SUBMITTED
                    ? IstTestSubtest::STATUS_COMPLETED
                    : IstTestSubtest::STATUS_TIMED_OUT,
                'locked_at' => $lockedAt,
                'finalized_reason' => $reason->value,
                'awarded_score' => $statistics['awarded_score'],
                'max_score' => $statistics['max_score'],
                'correct_count' => $statistics['correct_count'],
                'partial_count' => $statistics['partial_count'],
                'wrong_count' => $statistics['wrong_count'],
                'blank_count' => $statistics['blank_count'],
                'percentage' => $percentage,
            ]);

            if ($nextRuntime !== null) {
                $nextRuntime->update(['status' => IstTestSubtest::STATUS_INSTRUCTION]);
                $test->update(['current_subtest_sequence' => $runtime->sequence + 1]);
            } else {
                $this->completeOverallTest($runtime, $test, $now);
            }

            return $this->resultFromPersisted(
                $runtime->fresh('subtest'),
                $test->fresh(),
                false,
            );
        });
    }

    private function assertCanFinalize(
        IstTestSubtest $runtime,
        IstTest $test,
        IstFinalizationReason $reason,
        CarbonInterface $now,
        array $finalAnswers,
    ): void {
        if ($test->status !== IstTest::STATUS_IN_PROGRESS || $test->started_at === null) {
            throw new InvalidIstFinalizationException($runtime->id, 'parent test is not in progress');
        }

        if ($test->current_subtest_sequence !== $runtime->sequence) {
            throw new InvalidIstFinalizationException($runtime->id, 'subtest is not current');
        }

        if ($runtime->locked_at !== null) {
            throw new InvalidIstFinalizationException($runtime->id, 'non-terminal subtest is locked');
        }

        if ($runtime->answering_ends_at === null) {
            throw new InvalidIstFinalizationException($runtime->id, 'answering deadline is unavailable');
        }

        $expired = $this->timer->isExpired($runtime, $now);

        if ($reason === IstFinalizationReason::SUBMITTED) {
            if ($expired || $this->timer->getCurrentPhase($runtime, $now) !== IstSubtestPhase::ANSWERING) {
                throw new InvalidIstFinalizationException($runtime->id, 'submitted finalization is outside answering time');
            }

            return;
        }

        if ($finalAnswers !== []) {
            throw new InvalidIstFinalizationException($runtime->id, 'timeout cannot contain final answers');
        }

        if (! $expired) {
            throw new InvalidIstFinalizationException($runtime->id, 'timeout deadline has not been reached');
        }
    }

    private function scoreAnswers(
        IstTestSubtest $runtime,
        Collection $questions,
        CarbonInterface $now,
    ): array {
        $statistics = [
            'awarded_score' => 0.0,
            'max_score' => 0.0,
            'correct_count' => 0,
            'partial_count' => 0,
            'wrong_count' => 0,
            'blank_count' => 0,
        ];

        foreach ($questions->sortBy('id') as $question) {
            /** @var IstTestQuestion $question */
            $answer = IstAnswer::query()
                ->where('ist_test_question_id', $question->id)
                ->lockForUpdate()
                ->first();

            if ($answer === null) {
                $answer = IstAnswer::create([
                    'ist_test_question_id' => $question->id,
                    'selected_option_key' => null,
                    'numeric_answer' => null,
                    'awarded_score' => 0,
                    'outcome' => IstAnswer::OUTCOME_BLANK,
                    'client_revision' => 0,
                    'saved_at' => CarbonImmutable::instance($now),
                ]);
            }

            $score = $this->scoreQuestion($runtime, $question, $answer);
            $answer->update($score);

            $statistics['awarded_score'] += (float) $score['awarded_score'];

            $statistics['max_score'] += $this->calculator->weightedMaxScore(
                $question->max_score,
                self::NEUTRAL_DIFFICULTY,
            );

            $statistics[$score['outcome'].'_count']++;
        }

        if (array_sum([
            $statistics['correct_count'],
            $statistics['partial_count'],
            $statistics['wrong_count'],
            $statistics['blank_count'],
        ]) !== $runtime->question_count) {
            throw new InvalidIstFinalizationException($runtime->id, 'answer statistics are inconsistent');
        }

        return $statistics;
    }

    private function scoreQuestion(
    IstTestSubtest $runtime,
    IstTestQuestion $question,
    IstAnswer $answer,
): array {
    try {
        return match ($question->answer_type) {
            IstAnswerType::SINGLE_CHOICE,
            IstAnswerType::IMAGE_CHOICE => $this->calculator->scoreBinary(
                $answer->selected_option_key === null
                    ? null
                    : $answer->selected_option_key
                        === ($question->answer_key_snapshot['correct_option_key'] ?? null),
                self::NEUTRAL_DIFFICULTY,
            ),

            IstAnswerType::SINGLE_CHOICE_WEIGHTED => $this->scoreWeighted(
                $question,
                $answer,
            ),

            IstAnswerType::NUMERIC => $this->calculator->scoreNumeric(
                $answer->numeric_answer,
                $question->answer_key_snapshot['numeric_answer'] ?? throw new InvalidIstFinalizationException(
                    $runtime->id,
                    'numeric answer key snapshot is unavailable',
                ),
                self::NEUTRAL_DIFFICULTY,
            ),

            default => throw new InvalidIstFinalizationException(
                $runtime->id,
                'snapshot answer type is unsupported',
            ),
        };
    } catch (InvalidIstFinalizationException $exception) {
        throw $exception;
    } catch (Throwable) {
        throw new InvalidIstFinalizationException(
            $runtime->id,
            'snapshot scoring data is invalid',
        );
    }
}

    private function scoreWeighted(
        IstTestQuestion $question,
        IstAnswer $answer,
    ): array {
        if ($answer->selected_option_key === null) {
            return $this->calculator->scoreWeighted(
                null,
                self::NEUTRAL_DIFFICULTY,
            );
        }

        $scores = $question->answer_key_snapshot['scores'] ?? [];

        if (! array_key_exists($answer->selected_option_key, $scores)) {
            throw new InvalidIstFinalizationException(
                $question->ist_test_subtest_id,
                'selected weighted option is unavailable in snapshot',
            );
        }

        return $this->calculator->scoreWeighted(
            $scores[$answer->selected_option_key],
            self::NEUTRAL_DIFFICULTY,
        );
    }

    private function completeOverallTest(
        IstTestSubtest $runtime,
        IstTest $test,
        CarbonInterface $now,
    ): void {
        if ($runtime->sequence !== 9) {
            throw new InvalidIstFinalizationException($runtime->id, 'last runtime sequence is invalid');
        }

        $runtimes = IstTestSubtest::query()
            ->with('subtest')
            ->where('ist_test_id', $test->id)
            ->orderBy('sequence')
            ->get();
        $terminal = $runtimes->filter(fn (IstTestSubtest $item): bool => $this->isTerminal($item));

        if ($runtimes->count() !== 9 || $terminal->count() !== 9) {
            throw new InvalidIstFinalizationException($runtime->id, 'exactly nine finalized subtests are required');
        }

        $subtestCodes = [];

        foreach ($terminal as $item) {
            if (! $item->subtest) {
                throw new InvalidIstFinalizationException(
                    $runtime->id,
                    'a finalized subtest score is unavailable',
                );
            }

            $subtestCodes[] = strtoupper(
                trim((string) $item->subtest->code)
            );
        }

        if (count(array_unique($subtestCodes)) !== 9) {
            throw new InvalidIstFinalizationException(
                $runtime->id,
                'exactly nine unique finalized subtest scores are required',
            );
        }

        // Raw score -> standard score -> IQ conversion is intentionally not
        // done here. It is computed on demand in IstResultService from the
        // persisted awarded_score per subtest, so results self-heal as soon
        // as IST age norm data becomes available, instead of freezing an
        // incomplete/invalid IQ at finalization time.
        $test->update([
            'status' => IstTest::STATUS_COMPLETED,
            'finished_at' => CarbonImmutable::instance($now),
        ]);
    }

    private function resultFromPersisted(
        IstTestSubtest $runtime,
        IstTest $test,
        bool $alreadyFinalized,
    ): IstSubtestFinalizationResult {
        $persistedReason = IstFinalizationReason::tryFrom((string) $runtime->finalized_reason);

        if (! $persistedReason || $runtime->locked_at === null || ! $this->isTerminal($runtime)) {
            throw new InvalidIstFinalizationException($runtime->id, 'persisted final state is inconsistent');
        }

        $subtest = $runtime->relationLoaded('subtest')
            ? $runtime->subtest
            : $runtime->subtest()->first();

        if (! $subtest) {
            throw new InvalidIstFinalizationException($runtime->id, 'master subtest is unavailable');
        }

        $nextId = $runtime->sequence < 9
            ? IstTestSubtest::query()
                ->where('ist_test_id', $test->id)
                ->where('sequence', $runtime->sequence + 1)
                ->value('id')
            : null;

        return new IstSubtestFinalizationResult(
            testSubtestId: $runtime->id,
            subtestCode: $subtest->code,
            sequence: $runtime->sequence,
            status: $runtime->status,
            persistedReason: $persistedReason,
            alreadyFinalized: $alreadyFinalized,
            awardedScore: (float) $runtime->awarded_score,
            maxScore: (float) $runtime->max_score,
            correctCount: $runtime->correct_count,
            partialCount: $runtime->partial_count,
            wrongCount: $runtime->wrong_count,
            blankCount: $runtime->blank_count,
            percentage: (float) $runtime->percentage,
            lockedAt: CarbonImmutable::instance($runtime->locked_at),
            nextTestSubtestId: $nextId,
            overallCompleted: $test->status === IstTest::STATUS_COMPLETED,
        );
    }

    private function isTerminal(IstTestSubtest $runtime): bool
    {
        return in_array($runtime->status, [
            IstTestSubtest::STATUS_COMPLETED,
            IstTestSubtest::STATUS_TIMED_OUT,
        ], true);
    }
}
