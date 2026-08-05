<?php

namespace Tests\Feature\Ist\Http;

use App\Models\Ist\IstQuestion;
use App\Models\Ist\IstQuestionOption;
use App\Models\Ist\IstTest;
use App\Models\Ist\IstTestQuestion;
use App\Models\Ist\IstTestSubtest;
use App\Services\Ist\IstTestLifecycleService;
use App\Support\Ist\IstAnswerType;
use Carbon\CarbonImmutable;
use Tests\Feature\Ist\Services\IstDatabaseTestCase;

abstract class IstHttpTestCase extends IstDatabaseTestCase
{
    protected CarbonImmutable $now;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $manifest = public_path('build/manifest.json');

        if (is_file($manifest)) {
            $this->withHeader('X-Inertia-Version', hash_file('xxh128', $manifest));
        }

        $this->now = CarbonImmutable::parse('2026-08-05 10:00:00', 'UTC');
        CarbonImmutable::setTestNow($this->now);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    protected function createQuestionBank(int $subtestLimit = 9): void
    {
        $subtests = \App\Models\Ist\IstSubtest::query()
            ->where('is_active', true)
            ->orderBy('sequence')
            ->limit($subtestLimit)
            ->get();

        foreach ($subtests as $subtest) {
            for ($number = 1; $number <= $subtest->question_count; $number++) {
                $type = $subtest->default_answer_type;
                $question = IstQuestion::create([
                    'ist_subtest_id' => $subtest->id,
                    'question_number' => $number,
                    'display_order' => $number,
                    'kind' => IstQuestion::KIND_SCORED,
                    'answer_type' => $type,
                    'prompt' => "Temporary structure fixture {$subtest->code} {$number}",
                    'numeric_answer_key' => $type === IstAnswerType::NUMERIC ? 10 : null,
                    'max_score' => $type === IstAnswerType::SINGLE_CHOICE_WEIGHTED ? 4 : 1,
                    'version' => 1,
                    'is_active' => true,
                ]);

                if ($type !== IstAnswerType::NUMERIC) {
                    foreach (['A', 'B', 'C', 'D', 'E'] as $index => $key) {
                        $weighted = $type === IstAnswerType::SINGLE_CHOICE_WEIGHTED;
                        $scores = [0, 4, 1, 2, 3];
                        $score = $weighted ? $scores[$index] : ($key === 'B' ? 1 : 0);

                        IstQuestionOption::create([
                            'ist_question_id' => $question->id,
                            'option_key' => $key,
                            'option_text' => "Option {$key}",
                            'display_order' => $index + 1,
                            'is_correct' => $key === 'B',
                            'score_value' => $score,
                            'is_active' => true,
                        ]);
                    }
                }
            }
        }
    }

    protected function createOwnedTest(string $participant = 'HTTP Participant'): array
    {
        $result = app(IstTestLifecycleService::class)->create([
            'participant_name' => $participant,
            'age' => 30,
            'gender' => 'L',
        ]);
        $token = $result->takeRawAccessToken();
        $test = $result->test;

        $this->withSession([
            'ist' => [
                'access_tokens' => [
                    $test->public_id => $token,
                ],
            ],
        ]);

        return [$test, $token];
    }

    protected function activateFirstRuntime(
        IstTest $test,
        ?CarbonImmutable $deadline = null,
    ): array {
        $deadline ??= $this->now->addMinute();
        $runtime = $test->subtests()->where('sequence', 1)->firstOrFail();
        $test->update([
            'status' => IstTest::STATUS_IN_PROGRESS,
            'started_at' => $this->now->subHour(),
            'current_subtest_sequence' => 1,
        ]);
        $runtime->update([
            'status' => IstTestSubtest::STATUS_ANSWERING,
            'question_count' => 1,
            'started_at' => $this->now->subMinute(),
            'answering_started_at' => $this->now->subMinute(),
            'answering_ends_at' => $deadline,
        ]);

        $question = $this->createSnapshotQuestion($runtime);

        return [$runtime->fresh('subtest'), $question];
    }

    protected function createSnapshotQuestion(
        IstTestSubtest $runtime,
        int $order = 1,
        ?string $imagePath = null,
    ): IstTestQuestion {
        $keys = ['A', 'B', 'C', 'D', 'E'];

        return IstTestQuestion::create([
            'ist_test_subtest_id' => $runtime->id,
            'source_question_id' => null,
            'display_order' => $order,
            'answer_type' => IstAnswerType::SINGLE_CHOICE,
            'question_snapshot' => [
                'prompt' => 'Safe participant prompt',
                'image_disk' => 'public',
                'image_path' => $imagePath,
                'image_alt' => 'Question image',
            ],
            'options_snapshot' => array_map(
                static fn (string $key, int $index): array => [
                    'option_key' => $key,
                    'option_text' => "Option {$key}",
                    'image_disk' => 'public',
                    'image_path' => null,
                    'image_alt' => null,
                    'display_order' => $index + 1,
                ],
                $keys,
                array_keys($keys),
            ),
            'answer_key_snapshot' => [
                'correct_option_key' => 'B',
                'scores' => ['A' => 0, 'B' => 1, 'C' => 0, 'D' => 0, 'E' => 0],
            ],
            'max_score' => 1,
        ]);
    }

    protected function sessionKey(IstTest $test): string
    {
        return "ist.access_tokens.{$test->public_id}";
    }
}
