<?php

namespace Tests\Feature\Ist\Http;

use App\Models\Ist\IstAnswer;
use App\Models\Ist\IstQuestion;
use App\Models\Ist\IstTest;
use App\Models\Ist\IstTestSubtest;
use App\Support\Ist\IstAnswerType;

class IstNavigationHttpTest extends IstHttpTestCase
{
    public function test_instruction_is_read_only_does_not_start_timer_and_payload_is_safe(): void
    {
        [$test] = $this->createOwnedTest();
        $runtime = $test->subtests()->where('sequence', 1)->firstOrFail();
        $runtime->update(['question_count' => 1]);
        $this->createSnapshotQuestion($runtime);

        IstQuestion::create([
            'ist_subtest_id' => $runtime->ist_subtest_id,
            'question_number' => 1,
            'display_order' => 1,
            'kind' => IstQuestion::KIND_EXAMPLE,
            'answer_type' => IstAnswerType::SINGLE_CHOICE,
            'prompt' => 'Safe example',
            'image_disk' => 'local',
            'image_path' => '/home/private/example.png',
            'image_alt' => 'Example image',
            'max_score' => 1,
            'is_active' => true,
        ]);

        $response = $this->withHeader('X-Inertia', 'true')->get(route(
            'ist.subtests.instruction',
            ['test' => $test->public_id, 'subtest' => 'SE'],
        ));

        $response->assertOk()
            ->assertJsonPath('component', 'IST/Instruction')
            ->assertJsonPath('props.snapshotComplete', true)
            ->assertJsonPath('props.canStart', true)
            ->assertJsonPath('props.examples.0.image.url', null);

        $runtime->refresh();
        $this->assertNull($runtime->started_at);
        $this->assertNull($runtime->answering_ends_at);

        $json = $response->getContent();
        foreach ([
            '/home/private/example.png',
            'access_token_hash',
            'rawAccessToken',
            'answer_key_snapshot',
            'numeric_answer_key',
            'is_correct',
            'score_value',
        ] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $json);
        }
    }

    public function test_instruction_with_incomplete_snapshot_disables_start(): void
    {
        [$test] = $this->createOwnedTest();

        $this->withHeader('X-Inertia', 'true')->get(route(
            'ist.subtests.instruction',
            ['test' => $test->public_id, 'subtest' => 'SE'],
        ))->assertOk()
            ->assertJsonPath('props.snapshotComplete', false)
            ->assertJsonPath('props.canStart', false);
    }

    public function test_get_resume_expired_is_read_only_and_redirects_to_expired_work(): void
    {
        [$test] = $this->createOwnedTest();
        [$runtime] = $this->activateFirstRuntime($test, $this->now);
        $runtimeUpdatedAt = $runtime->updated_at->toISOString();
        $testUpdatedAt = $test->fresh()->updated_at->toISOString();

        $this->get(route('ist.resume', ['test' => $test->public_id]))
            ->assertStatus(303)
            ->assertRedirect(route('ist.subtests.work', [
                'test' => $test->public_id,
                'subtest' => 'SE',
            ]));

        $this->assertSame(IstTestSubtest::STATUS_ANSWERING, $runtime->fresh()->status);
        $this->assertNull($runtime->fresh()->locked_at);
        $this->assertSame($runtimeUpdatedAt, $runtime->fresh()->updated_at->toISOString());
        $this->assertSame($testUpdatedAt, $test->fresh()->updated_at->toISOString());
        $this->assertDatabaseCount('ist_answers', 0);
    }

    public function test_get_work_expired_returns_safe_mode_without_writes_and_only_post_timeout_finalizes(): void
    {
        [$test] = $this->createOwnedTest();
        [$runtime] = $this->activateFirstRuntime($test, $this->now);
        $runtimeUpdatedAt = $runtime->updated_at->toISOString();

        $response = $this->withHeader('X-Inertia', 'true')->get(route(
            'ist.subtests.work',
            ['test' => $test->public_id, 'subtest' => 'SE'],
        ));

        $response->assertOk()
            ->assertJsonPath('component', 'IST/Work')
            ->assertJsonPath('props.mode', 'expired')
            ->assertJsonPath('props.remainingSeconds', 0)
            ->assertJsonPath('props.finishUrl', route('ist.subtests.finish', [
                'test' => $test->public_id,
                'subtest' => 'SE',
            ]));
        $this->assertSame(IstTestSubtest::STATUS_ANSWERING, $runtime->fresh()->status);
        $this->assertSame($runtimeUpdatedAt, $runtime->fresh()->updated_at->toISOString());
        $this->assertDatabaseCount('ist_answers', 0);

        $this->post(route('ist.subtests.finish', [
            'test' => $test->public_id,
            'subtest' => 'SE',
        ]), ['reason' => 'timeout'])
            ->assertStatus(303)
            ->assertRedirect(route('ist.subtests.instruction', [
                'test' => $test->public_id,
                'subtest' => 'WA',
            ]));

        $runtime->refresh();
        $this->assertSame(IstTestSubtest::STATUS_TIMED_OUT, $runtime->status);
        $this->assertSame(IstTestSubtest::FINALIZED_TIMEOUT, $runtime->finalized_reason);
        $this->assertNotNull($runtime->locked_at);
        $this->assertSame(1, IstAnswer::query()->count());
    }

    public function test_future_or_old_subtest_redirects_to_current_canonical_instruction(): void
    {
        [$test] = $this->createOwnedTest();
        $test->update([
            'status' => IstTest::STATUS_IN_PROGRESS,
            'started_at' => $this->now->subHour(),
            'current_subtest_sequence' => 2,
        ]);
        $test->subtests()->where('sequence', 1)->update([
            'status' => IstTestSubtest::STATUS_COMPLETED,
            'locked_at' => $this->now->subMinute(),
            'finalized_reason' => IstTestSubtest::FINALIZED_SUBMITTED,
        ]);
        $test->subtests()->where('sequence', 2)->update([
            'status' => IstTestSubtest::STATUS_INSTRUCTION,
        ]);

        foreach (['SE', 'AN'] as $requested) {
            $this->get(route('ist.subtests.instruction', [
                'test' => $test->public_id,
                'subtest' => $requested,
            ]))->assertStatus(303)->assertRedirect(route('ist.subtests.instruction', [
                'test' => $test->public_id,
                'subtest' => 'WA',
            ]));
        }
    }

    public function test_me_memorization_uses_work_route_with_memorization_mode(): void
    {
        [$test] = $this->createOwnedTest();
        $test->update([
            'status' => IstTest::STATUS_IN_PROGRESS,
            'started_at' => $this->now->subHour(),
            'current_subtest_sequence' => 9,
        ]);
        $runtime = $test->subtests()->where('sequence', 9)->firstOrFail();
        $runtime->update([
            'status' => IstTestSubtest::STATUS_MEMORIZING,
            'question_count' => 1,
            'started_at' => $this->now,
            'memorization_started_at' => $this->now,
            'memorization_ends_at' => $this->now->addSeconds(120),
            'answering_started_at' => $this->now->addSeconds(120),
            'answering_ends_at' => $this->now->addSeconds(360),
        ]);
        $this->createSnapshotQuestion($runtime);

        $this->withHeader('X-Inertia', 'true')->get(route('ist.subtests.work', [
            'test' => $test->public_id,
            'subtest' => 'ME',
        ]))->assertOk()
            ->assertJsonPath('props.mode', 'memorization')
            ->assertJsonPath('props.remainingSeconds', 120)
            ->assertJsonPath('props.questions', []);
    }
}
