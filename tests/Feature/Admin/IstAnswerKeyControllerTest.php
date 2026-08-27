<?php

namespace Tests\Feature\Admin;

use App\Models\IstAnswerKey;
use App\Models\User;
use Tests\Feature\Ist\Services\IstDatabaseTestCase;

class IstAnswerKeyControllerTest extends IstDatabaseTestCase
{
    public function test_guest_cannot_access_answer_key_admin(): void
    {
        $this->get(route('admin.ist-answer-keys.index'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_can_create_answer_key(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('admin.ist-answer-keys.store'), [
            'subtest' => 'SE',
            'question_number' => 1,
            'correct_answer' => 'e',
            'score_weight' => 1,
        ]);

        $response->assertRedirect(route('admin.ist-answer-keys.index', ['subtest' => 'SE']));
        $this->assertDatabaseHas('ist_answer_keys', [
            'subtest' => 'SE',
            'question_number' => 1,
            'correct_answer' => 'e',
        ]);
    }

    public function test_duplicate_subtest_and_question_number_is_rejected(): void
    {
        $user = User::factory()->create();
        IstAnswerKey::create([
            'subtest' => 'SE',
            'question_number' => 1,
            'correct_answer' => 'e',
            'score_weight' => 1,
        ]);

        $response = $this->actingAs($user)->post(route('admin.ist-answer-keys.store'), [
            'subtest' => 'SE',
            'question_number' => 1,
            'correct_answer' => 'a',
            'score_weight' => 1,
        ]);

        $response->assertSessionHasErrors('question_number');
    }

    public function test_ge_answer_requires_valid_json(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('admin.ist-answer-keys.store'), [
            'subtest' => 'GE',
            'question_number' => 61,
            'correct_answer' => 'not json',
            'score_weight' => 2,
        ]);

        $response->assertSessionHasErrors('correct_answer');
    }

    public function test_admin_can_update_answer_key(): void
    {
        $user = User::factory()->create();
        $answerKey = IstAnswerKey::create([
            'subtest' => 'RA',
            'question_number' => 1,
            'correct_answer' => '35',
            'score_weight' => 1,
        ]);

        $response = $this->actingAs($user)->put(route('admin.ist-answer-keys.update', $answerKey->id), [
            'subtest' => 'RA',
            'question_number' => 1,
            'correct_answer' => '40',
            'score_weight' => 1,
        ]);

        $response->assertRedirect(route('admin.ist-answer-keys.index', ['subtest' => 'RA']));
        $this->assertSame('40', $answerKey->fresh()->correct_answer);
    }

    public function test_admin_can_delete_answer_key(): void
    {
        $user = User::factory()->create();
        $answerKey = IstAnswerKey::create([
            'subtest' => 'RA',
            'question_number' => 1,
            'correct_answer' => '35',
            'score_weight' => 1,
        ]);

        $response = $this->actingAs($user)->delete(route('admin.ist-answer-keys.destroy', $answerKey->id));

        $response->assertRedirect(route('admin.ist-answer-keys.index', ['subtest' => 'RA']));
        $this->assertDatabaseMissing('ist_answer_keys', ['id' => $answerKey->id]);
    }
}
