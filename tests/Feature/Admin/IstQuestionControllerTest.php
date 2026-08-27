<?php

namespace Tests\Feature\Admin;

use App\Models\Ist\IstQuestion;
use App\Models\Ist\IstSubtest;
use App\Models\User;
use App\Support\Ist\IstAnswerType;
use Tests\Feature\Ist\Http\IstHttpTestCase;

class IstQuestionControllerTest extends IstHttpTestCase
{
    public function test_guest_cannot_access_question_admin(): void
    {
        $this->get(route('admin.ist-questions.index'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_can_list_and_filter_questions(): void
    {
        $this->createQuestionBank();
        $user = User::factory()->create();
        $subtest = IstSubtest::where('code', 'SE')->firstOrFail();

        $response = $this->actingAs($user)
            ->withHeader('X-Inertia', 'true')
            ->get(route('admin.ist-questions.index', ['subtest_id' => $subtest->id]));

        $response->assertOk()
            ->assertJsonPath('component', 'Admin/IstQuestions/Index')
            ->assertJsonPath('props.questions.total', $subtest->question_count);
    }

    public function test_admin_can_create_single_choice_question(): void
    {
        $user = User::factory()->create();
        $subtest = IstSubtest::where('code', 'SE')->firstOrFail();

        $response = $this->actingAs($user)->post(route('admin.ist-questions.store'), [
            'ist_subtest_id' => $subtest->id,
            'kind' => IstQuestion::KIND_SCORED,
            'question_number' => 1,
            'display_order' => 1,
            'answer_type' => IstAnswerType::SINGLE_CHOICE,
            'prompt' => 'Contoh soal SE',
            'max_score' => 1,
            'difficulty' => 'medium',
            'is_active' => true,
            'options' => [
                ['option_key' => 'A', 'option_text' => 'Opsi A', 'is_correct' => false, 'score_value' => 0],
                ['option_key' => 'B', 'option_text' => 'Opsi B', 'is_correct' => true, 'score_value' => 1],
                ['option_key' => 'C', 'option_text' => 'Opsi C', 'is_correct' => false, 'score_value' => 0],
                ['option_key' => 'D', 'option_text' => 'Opsi D', 'is_correct' => false, 'score_value' => 0],
                ['option_key' => 'E', 'option_text' => 'Opsi E', 'is_correct' => false, 'score_value' => 0],
            ],
        ]);

        $response->assertRedirect(route('admin.ist-questions.index', ['subtest_id' => $subtest->id]));

        $question = IstQuestion::where('ist_subtest_id', $subtest->id)
            ->where('question_number', 1)
            ->firstOrFail();

        $this->assertSame('Contoh soal SE', $question->prompt);
        $this->assertCount(5, $question->options);
        $this->assertSame('B', $question->options()->where('is_correct', true)->first()->option_key);
    }

    public function test_binary_question_requires_exactly_one_correct_option(): void
    {
        $user = User::factory()->create();
        $subtest = IstSubtest::where('code', 'SE')->firstOrFail();

        $response = $this->actingAs($user)->post(route('admin.ist-questions.store'), [
            'ist_subtest_id' => $subtest->id,
            'kind' => IstQuestion::KIND_SCORED,
            'question_number' => 2,
            'display_order' => 2,
            'answer_type' => IstAnswerType::SINGLE_CHOICE,
            'prompt' => 'Soal tanpa jawaban benar',
            'max_score' => 1,
            'difficulty' => 'medium',
            'is_active' => true,
            'options' => [
                ['option_key' => 'A', 'option_text' => 'A', 'is_correct' => false, 'score_value' => 0],
                ['option_key' => 'B', 'option_text' => 'B', 'is_correct' => false, 'score_value' => 0],
                ['option_key' => 'C', 'option_text' => 'C', 'is_correct' => false, 'score_value' => 0],
                ['option_key' => 'D', 'option_text' => 'D', 'is_correct' => false, 'score_value' => 0],
                ['option_key' => 'E', 'option_text' => 'E', 'is_correct' => false, 'score_value' => 0],
            ],
        ]);

        $response->assertSessionHasErrors('options');
        $this->assertDatabaseMissing('ist_questions', [
            'ist_subtest_id' => $subtest->id,
            'question_number' => 2,
        ]);
    }

    public function test_weighted_question_requires_exactly_one_score_of_three(): void
    {
        $user = User::factory()->create();
        $subtest = IstSubtest::where('code', 'GE')->firstOrFail();

        $response = $this->actingAs($user)->post(route('admin.ist-questions.store'), [
            'ist_subtest_id' => $subtest->id,
            'kind' => IstQuestion::KIND_SCORED,
            'question_number' => 1,
            'display_order' => 1,
            'answer_type' => IstAnswerType::SINGLE_CHOICE_WEIGHTED,
            'prompt' => 'Soal GE tanpa skor 3',
            'max_score' => 3,
            'difficulty' => 'medium',
            'is_active' => true,
            'options' => [
                ['option_key' => 'A', 'option_text' => 'A', 'is_correct' => false, 'score_value' => 2],
                ['option_key' => 'B', 'option_text' => 'B', 'is_correct' => false, 'score_value' => 2],
                ['option_key' => 'C', 'option_text' => 'C', 'is_correct' => false, 'score_value' => 1],
                ['option_key' => 'D', 'option_text' => 'D', 'is_correct' => false, 'score_value' => 0],
                ['option_key' => 'E', 'option_text' => 'E', 'is_correct' => false, 'score_value' => 0],
            ],
        ]);

        $response->assertSessionHasErrors('options');
    }

    public function test_numeric_question_requires_numeric_answer_key(): void
    {
        $user = User::factory()->create();
        $subtest = IstSubtest::where('code', 'RA')->firstOrFail();

        $response = $this->actingAs($user)->post(route('admin.ist-questions.store'), [
            'ist_subtest_id' => $subtest->id,
            'kind' => IstQuestion::KIND_SCORED,
            'question_number' => 1,
            'display_order' => 1,
            'answer_type' => IstAnswerType::NUMERIC,
            'prompt' => 'Berapa 2+2?',
            'max_score' => 1,
            'difficulty' => 'easy',
            'is_active' => true,
        ]);

        $response->assertSessionHasErrors('numeric_answer_key');
    }

    public function test_admin_can_update_question_and_replace_options(): void
    {
        $this->createQuestionBank();
        $user = User::factory()->create();
        $question = IstQuestion::where('question_number', 1)
            ->whereHas('subtest', fn ($query) => $query->where('code', 'SE'))
            ->firstOrFail();

        $response = $this->actingAs($user)->put(route('admin.ist-questions.update', $question->id), [
            'ist_subtest_id' => $question->ist_subtest_id,
            'kind' => $question->kind,
            'question_number' => $question->question_number,
            'display_order' => $question->display_order,
            'answer_type' => IstAnswerType::SINGLE_CHOICE,
            'prompt' => 'Prompt sudah diubah',
            'max_score' => 1,
            'difficulty' => 'hard',
            'is_active' => true,
            'options' => [
                ['option_key' => 'A', 'option_text' => 'Baru A', 'is_correct' => true, 'score_value' => 1],
                ['option_key' => 'B', 'option_text' => 'Baru B', 'is_correct' => false, 'score_value' => 0],
                ['option_key' => 'C', 'option_text' => 'Baru C', 'is_correct' => false, 'score_value' => 0],
                ['option_key' => 'D', 'option_text' => 'Baru D', 'is_correct' => false, 'score_value' => 0],
                ['option_key' => 'E', 'option_text' => 'Baru E', 'is_correct' => false, 'score_value' => 0],
            ],
        ]);

        $response->assertRedirect(route('admin.ist-questions.index', ['subtest_id' => $question->ist_subtest_id]));

        $question->refresh();
        $this->assertSame('Prompt sudah diubah', $question->prompt);
        $this->assertSame('hard', $question->difficulty);
        $this->assertCount(5, $question->options()->get());
        $this->assertSame('A', $question->options()->where('is_correct', true)->first()->option_key);
    }

    public function test_admin_can_delete_question(): void
    {
        $this->createQuestionBank();
        $user = User::factory()->create();
        $question = IstQuestion::where('question_number', 1)
            ->whereHas('subtest', fn ($query) => $query->where('code', 'SE'))
            ->firstOrFail();

        $response = $this->actingAs($user)->delete(route('admin.ist-questions.destroy', $question->id));

        $response->assertRedirect(route('admin.ist-questions.index', ['subtest_id' => $question->ist_subtest_id]));
        $this->assertSoftDeleted('ist_questions', ['id' => $question->id]);
    }
}
