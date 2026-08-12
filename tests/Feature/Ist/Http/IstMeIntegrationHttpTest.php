<?php

namespace Tests\Feature\Ist\Http;

use App\Models\Ist\IstAnswer;
use App\Models\Ist\IstQuestion;
use App\Models\Ist\IstQuestionOption;
use App\Models\Ist\IstTest;
use App\Models\Ist\IstTestSubtest;
use App\Services\Ist\IstQuestionSnapshotService;
use App\Support\Ist\IstAnswerType;
use Carbon\CarbonImmutable;

final class IstMeIntegrationHttpTest extends IstHttpTestCase
{
    public function test_example_completion_is_required_persistent_and_unscored(): void
    {
        [$test, $runtime] = $this->prepareMeInstruction();
        $instructionUrl = $this->instructionUrl($test);
        $startUrl = $this->startUrl($test);

        $beforeCompletion = $this->withHeader('X-Inertia', 'true')->get($instructionUrl);
        $beforeCompletion
            ->assertOk()
            ->assertJsonPath(
                'props.subtest.instructionContent',
                'Hafalkan lima kelompok kata. Setelah materi ditutup, pilih kategori berdasarkan huruf awal.',
            )
            ->assertJsonPath('props.requiresExampleCompletion', true)
            ->assertJsonPath('props.exampleCompleted', false)
            ->assertJsonPath('props.canStart', false)
            ->assertJsonPath('props.examples.0.explanation', null);
        $this->assertStringNotContainsString('Safe ME example feedback', $beforeCompletion->getContent());
        $this->assertStringNotContainsString('pasangan kata', strtolower($beforeCompletion->getContent()));

        $this->post($startUrl)->assertStatus(409);
        $this->assertNull($runtime->fresh()->started_at);

        $this->post($this->exampleUrl($test), [
            'selected_option_key' => 'A',
        ])->assertStatus(303)->assertRedirect($instructionUrl);

        $runtime->refresh();
        $this->assertSame($this->now->toISOString(), $runtime->instruction_viewed_at->toISOString());
        $this->assertNull($runtime->started_at);
        $this->assertNull($runtime->memorization_ends_at);
        $this->assertNull($runtime->answering_ends_at);
        $this->assertSame('0.0000', $runtime->awarded_score);
        $this->assertSame(0, IstAnswer::query()->count());

        $this->withHeader('X-Inertia', 'true')->get($instructionUrl)
            ->assertOk()
            ->assertJsonPath('props.exampleCompleted', true)
            ->assertJsonPath('props.canStart', true)
            ->assertJsonPath('props.examples.0.explanation', 'Safe ME example feedback');
    }

    public function test_completion_is_idempotent_and_me_start_keeps_single_server_deadline(): void
    {
        [$test, $runtime] = $this->prepareMeInstruction();
        $exampleUrl = $this->exampleUrl($test);

        $this->post($exampleUrl, ['selected_option_key' => 'B'])->assertStatus(303);
        $completedAt = $runtime->fresh()->instruction_viewed_at->toISOString();

        CarbonImmutable::setTestNow($this->now->addSeconds(30));
        $this->post($exampleUrl, ['selected_option_key' => 'C'])->assertStatus(303);
        $this->assertSame($completedAt, $runtime->fresh()->instruction_viewed_at->toISOString());

        $startedAt = $this->now->addSeconds(30);
        $this->post($this->startUrl($test))->assertStatus(303)->assertRedirect($this->workUrl($test));
        $runtime->refresh();
        $originalMemorizationEnd = $runtime->memorization_ends_at->toISOString();
        $originalAnsweringEnd = $runtime->answering_ends_at->toISOString();

        $this->assertSame($startedAt->toISOString(), $runtime->started_at->toISOString());
        $this->assertSame($startedAt->addSeconds(120)->toISOString(), $originalMemorizationEnd);
        $this->assertSame($startedAt->addSeconds(360)->toISOString(), $originalAnsweringEnd);

        CarbonImmutable::setTestNow($startedAt->addMinute());
        $this->post($this->startUrl($test))->assertStatus(303);
        $runtime->refresh();
        $this->assertSame($originalMemorizationEnd, $runtime->memorization_ends_at->toISOString());
        $this->assertSame($originalAnsweringEnd, $runtime->answering_ends_at->toISOString());
        $this->assertSame(0, IstAnswer::query()->count());
    }

    public function test_instruction_and_groups_are_read_from_the_immutable_session_snapshot(): void
    {
        [$test, $runtime] = $this->prepareMeInstruction();
        $runtime->subtest()->update([
            'instruction_content' => 'Hafalkan pasangan kata lama.',
            'memorization_content' => json_encode([
                'pairs' => [['cue' => 'legacy-cue', 'associate' => 'legacy-associate']],
            ], JSON_THROW_ON_ERROR),
        ]);

        $instruction = $this->withHeader('X-Inertia', 'true')->get($this->instructionUrl($test));
        $instruction->assertOk()->assertJsonPath(
            'props.subtest.instructionContent',
            'Hafalkan lima kelompok kata. Setelah materi ditutup, pilih kategori berdasarkan huruf awal.',
        );
        $this->assertStringNotContainsString('legacy-cue', $instruction->getContent());
        $this->assertStringNotContainsString('pasangan kata lama', strtolower($instruction->getContent()));

        $this->post($this->exampleUrl($test), ['selected_option_key' => 'B'])->assertStatus(303);
        $this->post($this->startUrl($test))->assertStatus(303);

        $work = $this->withHeader('X-Inertia', 'true')->get($this->workUrl($test));
        $work->assertOk()
            ->assertJsonCount(5, 'props.memorizationGroups')
            ->assertJsonCount(5, 'props.memorizationGroups.0.words');
        $this->assertStringNotContainsString('legacy-cue', $work->getContent());
        $this->assertStringNotContainsString('memorizationPairs', $work->getContent());
    }

    public function test_memorization_payload_is_minimal_and_disappears_at_answering_boundary(): void
    {
        [$test, $runtime, $groups] = $this->prepareMeInstruction();
        $this->post($this->exampleUrl($test), ['selected_option_key' => 'B'])->assertStatus(303);
        $this->post($this->startUrl($test))->assertStatus(303);

        $response = $this->withHeader('X-Inertia', 'true')->get($this->workUrl($test));
        $response->assertOk()
            ->assertJsonPath('props.mode', 'memorization')
            ->assertJsonCount(5, 'props.memorizationGroups')
            ->assertJsonPath('props.memorizationGroups.0', [
                'key' => 'A',
                'name' => 'Kategori A',
                'words' => ['Akata', 'Bkata', 'Ckata', 'Dkata', 'Ekata'],
                'displayOrder' => 1,
            ])
            ->assertJsonPath('props.questions', []);

        $this->assertStringNotContainsString('memorizationPairs', $response->getContent());

        $json = $response->getContent();
        foreach ([
            'tested',
            'filler',
            'logical_id',
            'question_logical_id',
            'answer_key_snapshot',
            'is_correct',
            'score_value',
            'rationale',
            'source_reference',
            'target_word',
            'target_initial',
        ] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $json);
        }

        CarbonImmutable::setTestNow($runtime->fresh()->memorization_ends_at);
        $answering = $this->withHeader('X-Inertia', 'true')->get($this->workUrl($test));
        $answering->assertOk()
            ->assertJsonPath('props.mode', 'answering')
            ->assertJsonPath('props.memorizationGroups', [])
            ->assertJsonCount(1, 'props.questions');

        foreach ($groups as $group) {
            foreach ($group['words'] as $word) {
                $this->assertStringNotContainsString($word, $answering->getContent());
            }
        }

        $this->withHeader('X-Inertia', 'true')->get($this->workUrl($test))
            ->assertOk()
            ->assertJsonPath('props.memorizationGroups', []);
        $this->get($this->instructionUrl($test))
            ->assertStatus(303)
            ->assertRedirect($this->workUrl($test));
    }

    public function test_autosave_is_rejected_during_me_memorization(): void
    {
        [$test, , , $question] = $this->prepareMeInstruction();
        $this->post($this->exampleUrl($test), ['selected_option_key' => 'B'])->assertStatus(303);
        $this->post($this->startUrl($test))->assertStatus(303);

        $this->putJson(route('ist.subtests.answers.update', [
            'test' => $test->public_id,
            'subtest' => 'ME',
        ]), ['changes' => [[
            'ist_test_question_id' => $question->id,
            'selected_option_key' => 'B',
            'client_revision' => 1,
        ]]])->assertStatus(409)->assertJsonPath('code', 'IST_SUBTEST_STATE_CONFLICT');

        $this->assertSame(0, IstAnswer::query()->count());
    }

    private function prepareMeInstruction(): array
    {
        [$test] = $this->createOwnedTest('ME integration participant');
        $test->update([
            'status' => IstTest::STATUS_IN_PROGRESS,
            'started_at' => $this->now->subHour(),
            'current_subtest_sequence' => 9,
        ]);
        $test->subtests()->where('sequence', '<', 9)->update([
            'status' => IstTestSubtest::STATUS_COMPLETED,
            'locked_at' => $this->now->subMinute(),
            'finalized_reason' => IstTestSubtest::FINALIZED_SUBMITTED,
        ]);

        $runtime = $test->subtests()->where('sequence', 9)->firstOrFail();
        $runtime->update([
            'status' => IstTestSubtest::STATUS_INSTRUCTION,
            'question_count' => 1,
        ]);

        $initials = range('A', 'Y');
        $groups = [];

        foreach (['A', 'B', 'C', 'D', 'E'] as $groupIndex => $key) {
            $groups[] = [
                'key' => $key,
                'name' => "Kategori {$key}",
                'words' => array_map(
                    static fn (string $initial): string => $initial.'kata',
                    array_slice($initials, $groupIndex * 5, 5),
                ),
                'display_order' => $groupIndex + 1,
            ];
        }

        $runtime->subtest()->update([
            'instruction_content' => 'Hafalkan lima kelompok kata. Setelah materi ditutup, pilih kategori berdasarkan huruf awal.',
            'memorization_content' => json_encode(['groups' => $groups], JSON_THROW_ON_ERROR),
        ]);

        IstQuestion::query()
            ->where('ist_subtest_id', $runtime->ist_subtest_id)
            ->update(['is_active' => false]);

        $scored = IstQuestion::create([
            'ist_subtest_id' => $runtime->ist_subtest_id,
            'question_number' => 1,
            'display_order' => 1,
            'kind' => IstQuestion::KIND_SCORED,
            'answer_type' => IstAnswerType::SINGLE_CHOICE,
            'prompt' => 'Kata yang mempunyai huruf permulaan “A” berada pada kelompok ...',
            'max_score' => 1,
            'difficulty' => 'easy',
            'version' => 1,
            'is_active' => true,
        ]);
        foreach (['A', 'B', 'C', 'D', 'E'] as $index => $key) {
            IstQuestionOption::create([
                'ist_question_id' => $scored->id,
                'option_key' => $key,
                'option_text' => "Kategori {$key}",
                'display_order' => $index + 1,
                'is_correct' => $key === 'A',
                'score_value' => $key === 'A' ? 1 : 0,
                'is_active' => true,
            ]);
        }

        $question = app(IstQuestionSnapshotService::class)->snapshot(
            $runtime->fresh('subtest'),
        )->firstOrFail();

        $example = IstQuestion::create([
            'ist_subtest_id' => $runtime->ist_subtest_id,
            'question_number' => 1,
            'display_order' => 1,
            'kind' => IstQuestion::KIND_EXAMPLE,
            'answer_type' => IstAnswerType::SINGLE_CHOICE,
            'prompt' => 'Safe ME example prompt',
            'example_explanation' => 'Safe ME example feedback',
            'max_score' => 1,
            'version' => 1,
            'is_active' => true,
        ]);
        foreach (['A', 'B', 'C', 'D', 'E'] as $index => $key) {
            IstQuestionOption::create([
                'ist_question_id' => $example->id,
                'option_key' => $key,
                'option_text' => "Example option {$key}",
                'display_order' => $index + 1,
                'is_correct' => $key === 'B',
                'score_value' => $key === 'B' ? 1 : 0,
                'is_active' => true,
            ]);
        }

        return [$test->fresh(), $runtime->fresh('subtest'), $groups, $question];
    }

    private function instructionUrl(IstTest $test): string
    {
        return route('ist.subtests.instruction', ['test' => $test->public_id, 'subtest' => 'ME']);
    }

    private function exampleUrl(IstTest $test): string
    {
        return route('ist.subtests.example.complete', ['test' => $test->public_id, 'subtest' => 'ME']);
    }

    private function startUrl(IstTest $test): string
    {
        return route('ist.subtests.start', ['test' => $test->public_id, 'subtest' => 'ME']);
    }

    private function workUrl(IstTest $test): string
    {
        return route('ist.subtests.work', ['test' => $test->public_id, 'subtest' => 'ME']);
    }
}
