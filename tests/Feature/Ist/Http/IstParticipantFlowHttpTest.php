<?php

namespace Tests\Feature\Ist\Http;

use App\Models\Ist\IstAnswer;
use App\Models\Ist\IstTest;
use App\Models\Ist\IstTestSubtest;

class IstParticipantFlowHttpTest extends IstHttpTestCase
{
    public function test_start_uses_server_timestamp_is_idempotent_and_rejects_incomplete_snapshot(): void
    {
        [$test] = $this->createOwnedTest();
        $runtime = $test->subtests()->where('sequence', 1)->firstOrFail();
        $runtime->update(['question_count' => 1]);
        $this->createSnapshotQuestion($runtime);
        $url = route('ist.subtests.start', [
            'test' => $test->public_id,
            'subtest' => 'SE',
        ]);

        $response = $this->post($url);
        $response->assertStatus(303)->assertRedirect(route('ist.subtests.work', [
            'test' => $test->public_id,
            'subtest' => 'SE',
        ]));

        $runtime->refresh();
        $originalDeadline = $runtime->answering_ends_at->toISOString();
        $this->assertSame($this->now->toISOString(), $runtime->started_at->toISOString());
        $this->assertSame($this->now->addSeconds(400)->toISOString(), $originalDeadline);
        $this->assertSame($this->now->toISOString(), $test->fresh()->started_at->toISOString());

        $this->post($url)->assertStatus(303);
        $this->assertSame($originalDeadline, $runtime->fresh()->answering_ends_at->toISOString());

        [$other] = $this->createOwnedTest('Incomplete start');
        $this->post(route('ist.subtests.start', [
            'test' => $other->public_id,
            'subtest' => 'SE',
        ]))->assertStatus(409);
        $this->assertNull($other->subtests()->where('sequence', 1)->firstOrFail()->started_at);
    }

    public function test_autosave_valid_stale_conflict_validation_and_safe_response(): void
    {
        [$test] = $this->createOwnedTest();
        [$runtime, $question] = $this->activateFirstRuntime($test);
        $url = route('ist.subtests.answers.update', [
            'test' => $test->public_id,
            'subtest' => 'SE',
        ]);

        $response = $this->putJson($url, ['changes' => [[
            'ist_test_question_id' => $question->id,
            'selected_option_key' => 'B',
            'client_revision' => 2,
        ]]]);

        $response->assertOk()
            ->assertJsonPath('savedQuestionIds.0', $question->id)
            ->assertJsonMissingPath('awarded_score')
            ->assertJsonMissingPath('answer_key_snapshot');
        $this->assertDatabaseHas('ist_answers', [
            'ist_test_question_id' => $question->id,
            'selected_option_key' => 'B',
            'client_revision' => 2,
            'awarded_score' => null,
        ]);

        $this->putJson($url, ['changes' => [[
            'ist_test_question_id' => $question->id,
            'selected_option_key' => 'A',
            'client_revision' => 1,
        ]]])->assertOk()->assertJsonPath('ignoredStaleQuestionIds.0', $question->id);
        $this->assertSame('B', IstAnswer::query()->where('ist_test_question_id', $question->id)->value('selected_option_key'));

        $this->putJson($url, ['changes' => [[
            'ist_test_question_id' => $question->id,
            'selected_option_key' => 'A',
            'client_revision' => 2,
        ]]])->assertStatus(409)->assertJsonPath('code', 'IST_ANSWER_REVISION_CONFLICT');

        $this->putJson($url, ['changes' => [[
            'ist_test_question_id' => $question->id,
            'selected_option_key' => 'B',
            'client_revision' => 3,
            'score_value' => 4,
        ]]])->assertStatus(422);
        $this->assertSame(IstTestSubtest::STATUS_ANSWERING, $runtime->fresh()->status);
    }

    public function test_expired_or_locked_autosave_returns_conflict(): void
    {
        [$test] = $this->createOwnedTest();
        [$runtime, $question] = $this->activateFirstRuntime($test, $this->now);
        $url = route('ist.subtests.answers.update', [
            'test' => $test->public_id,
            'subtest' => 'SE',
        ]);
        $payload = ['changes' => [[
            'ist_test_question_id' => $question->id,
            'selected_option_key' => 'B',
            'client_revision' => 1,
        ]]];

        $this->putJson($url, $payload)->assertStatus(409);
        $runtime->update([
            'answering_ends_at' => $this->now->addMinute(),
            'locked_at' => $this->now,
        ]);
        $this->putJson($url, $payload)->assertStatus(409);
        $this->assertDatabaseCount('ist_answers', 0);
    }

    public function test_work_payload_uses_null_image_url_and_contains_no_server_secrets(): void
    {
        [$test] = $this->createOwnedTest();
        $this->activateFirstRuntime($test);

        $response = $this->withHeader('X-Inertia', 'true')->get(route('ist.subtests.work', [
            'test' => $test->public_id,
            'subtest' => 'SE',
        ]));

        $response->assertOk()
            ->assertJsonPath('props.mode', 'answering')
            ->assertJsonPath('props.questions.0.image.url', null);

        foreach ([
            'access_token_hash',
            'rawAccessToken',
            'answer_key_snapshot',
            'numeric_answer_key',
            'is_correct',
            'score_value',
        ] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $response->getContent());
        }
    }

    public function test_submitted_before_deadline_scores_and_late_submitted_becomes_timeout_without_late_answer(): void
    {
        [$test] = $this->createOwnedTest();
        [, $question] = $this->activateFirstRuntime($test);
        $url = route('ist.subtests.finish', [
            'test' => $test->public_id,
            'subtest' => 'SE',
        ]);

        $this->post($url, [
            'reason' => 'submitted',
            'final_answers' => [[
                'ist_test_question_id' => $question->id,
                'selected_option_key' => 'B',
                'client_revision' => 1,
            ]],
        ])->assertStatus(303)->assertRedirect(route('ist.subtests.instruction', [
            'test' => $test->public_id,
            'subtest' => 'WA',
        ]));
        $this->assertDatabaseHas('ist_answers', [
            'ist_test_question_id' => $question->id,
            'outcome' => IstAnswer::OUTCOME_CORRECT,
        ]);

        [$lateTest] = $this->createOwnedTest('Late submit');
        [$lateRuntime, $lateQuestion] = $this->activateFirstRuntime($lateTest, $this->now);
        $this->post(route('ist.subtests.finish', [
            'test' => $lateTest->public_id,
            'subtest' => 'SE',
        ]), [
            'reason' => 'submitted',
            'final_answers' => [[
                'ist_test_question_id' => $lateQuestion->id,
                'selected_option_key' => 'B',
                'client_revision' => 1,
            ]],
        ])->assertStatus(303);

        $lateAnswer = IstAnswer::query()->where('ist_test_question_id', $lateQuestion->id)->firstOrFail();
        $this->assertSame(IstTestSubtest::STATUS_TIMED_OUT, $lateRuntime->fresh()->status);
        $this->assertSame(IstTestSubtest::FINALIZED_TIMEOUT, $lateRuntime->fresh()->finalized_reason);
        $this->assertNull($lateAnswer->selected_option_key);
        $this->assertSame(IstAnswer::OUTCOME_BLANK, $lateAnswer->outcome);
    }

    public function test_result_is_unavailable_before_completion_and_safe_after_completion(): void
    {
        [$test] = $this->createOwnedTest();
        $url = route('ist.result', ['test' => $test->public_id]);
        $this->withHeader('X-Inertia', 'true')->get($url)->assertStatus(409);

        $test->update([
            'status' => IstTest::STATUS_COMPLETED,
            'started_at' => $this->now->subMinutes(45),
            'finished_at' => $this->now,
            'current_subtest_sequence' => 9,
            'total_internal_score' => 50,
        ]);
        foreach ($test->subtests()->get() as $runtime) {
            $runtime->update([
                'status' => IstTestSubtest::STATUS_COMPLETED,
                'locked_at' => $this->now,
                'finalized_reason' => IstTestSubtest::FINALIZED_SUBMITTED,
                'max_score' => 10,
                'correct_count' => 5,
                'blank_count' => 5,
                'percentage' => 50,
            ]);
        }

        $response = $this->withHeader('X-Inertia', 'true')->get($url);
        $response->assertOk()
            ->assertJsonPath('component', 'IST/Result')
            ->assertJsonPath('props.durationSeconds', 2700)
            ->assertJsonCount(9, 'props.subtests')
            ->assertJsonCount(9, 'props.graphPoints');

        foreach ([
            'access_token_hash',
            'rawAccessToken',
            'answer_key_snapshot',
            'selected_option_key',
            'numeric_answer',
        ] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $response->getContent());
        }
    }

    public function test_me_timeout_completion_redirects_to_result_without_sequence_ten(): void
    {
        [$test] = $this->createOwnedTest('ME finish');
        $test->update([
            'status' => IstTest::STATUS_IN_PROGRESS,
            'started_at' => $this->now->subMinutes(45),
            'current_subtest_sequence' => 9,
        ]);
        foreach ($test->subtests()->where('sequence', '<', 9)->get() as $runtime) {
            $runtime->update([
                'status' => IstTestSubtest::STATUS_COMPLETED,
                'locked_at' => $this->now->subMinute(),
                'finalized_reason' => IstTestSubtest::FINALIZED_SUBMITTED,
                'percentage' => 50,
            ]);
        }
        $me = $test->subtests()->where('sequence', 9)->firstOrFail();
        $me->update([
            'status' => IstTestSubtest::STATUS_ANSWERING,
            'question_count' => 1,
            'started_at' => $this->now->subMinutes(6),
            'memorization_started_at' => $this->now->subMinutes(6),
            'memorization_ends_at' => $this->now->subMinutes(4),
            'answering_started_at' => $this->now->subMinutes(4),
            'answering_ends_at' => $this->now,
        ]);
        $this->createSnapshotQuestion($me);

        $this->post(route('ist.subtests.finish', [
            'test' => $test->public_id,
            'subtest' => 'ME',
        ]), ['reason' => 'timeout'])
            ->assertStatus(303)
            ->assertRedirect(route('ist.result', ['test' => $test->public_id]));

        $this->assertSame(IstTest::STATUS_COMPLETED, $test->fresh()->status);
        $this->assertSame(9, $test->fresh()->current_subtest_sequence);
        $this->assertNull($test->subtests()->where('sequence', 10)->first());
    }
}
