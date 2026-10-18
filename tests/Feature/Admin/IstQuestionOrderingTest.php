<?php

namespace Tests\Feature\Admin;

use App\Models\Ist\IstQuestion;
use App\Models\Ist\IstSubtest;
use App\Models\Ist\IstTest;
use App\Models\Ist\IstTestSubtest;
use App\Models\User;
use App\Services\Ist\IstQuestionSnapshotService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use Tests\Feature\Ist\Http\IstHttpTestCase;

class IstQuestionOrderingTest extends IstHttpTestCase
{
    private IstSubtest $subtest;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
        $this->subtest = IstSubtest::where('code', 'RA')->firstOrFail();
    }

    private function payload(int $number, array $overrides = []): array
    {
        return array_replace([
            'ist_subtest_id' => $this->subtest->id,
            'kind' => 'scored', 'question_number' => $number, 'display_order' => $number,
            'answer_type' => 'numeric', 'numeric_answer_key' => 4, 'max_score' => 1,
            'difficulty' => 'medium', 'is_active' => true, 'prompt' => "Question {$number}",
        ], $overrides);
    }

    private function question(int $number, array $overrides = []): IstQuestion
    {
        return IstQuestion::create($this->payload($number, $overrides));
    }

    private function assertOrder(array $ids, ?int $subtestId = null, string $kind = 'scored'): void
    {
        $rows = IstQuestion::where('ist_subtest_id', $subtestId ?? $this->subtest->id)
            ->where('kind', $kind)->orderBy('question_number')->get();
        $this->assertSame($ids, $rows->pluck('id')->all());
        $expected = $ids ? range(1, count($ids)) : [];
        $this->assertSame($expected, $rows->pluck('question_number')->all());
        $this->assertSame($expected, $rows->pluck('display_order')->all());
    }

    public function test_delete_compacts_numbers_and_insert_reuses_archived_position(): void
    {
        $a = $this->question(1);
        $b = $this->question(2);
        $c = $this->question(3);
        $example = $this->question(2, ['kind' => 'example']);
        $inactive = $this->question(4, ['is_active' => false]);
        $this->delete(route('admin.ist-questions.destroy', $b))->assertSessionHasNoErrors();
        $this->assertOrder([$a->id, $c->id, $inactive->id]);
        $this->assertSoftDeleted('ist_questions', ['id' => $b->id, 'question_number' => 2]);
        $this->assertSame(2, $example->fresh()->question_number);
        $this->assertSame(2, $this->subtest->fresh()->question_count);

        $this->post(route('admin.ist-questions.store'), $this->payload(2, ['prompt' => 'Inserted']))
            ->assertSessionHasNoErrors()->assertRedirect();
        $inserted = IstQuestion::where('prompt', 'Inserted')->firstOrFail();
        $this->assertOrder([$a->id, $inserted->id, $c->id, $inactive->id]);
        $this->assertSame(3, $this->subtest->fresh()->question_count);
    }

    public function test_move_in_both_directions_shifts_other_questions(): void
    {
        $a = $this->question(1);
        $b = $this->question(2);
        $c = $this->question(3);
        $this->put(route('admin.ist-questions.update', $c), $this->payload(1))
            ->assertSessionHasNoErrors();
        $this->assertOrder([$c->id, $a->id, $b->id]);
        $this->put(route('admin.ist-questions.update', $c), $this->payload(3))
            ->assertSessionHasNoErrors();
        $this->assertOrder([$a->id, $b->id, $c->id]);
    }

    public function test_move_between_subtests_and_kinds_compacts_both_groups(): void
    {
        $a = $this->question(1);
        $b = $this->question(2);
        $other = IstSubtest::where('code', 'ZR')->firstOrFail();
        $target = $this->question(1, ['ist_subtest_id' => $other->id, 'kind' => 'example']);
        $this->put(route('admin.ist-questions.update', $a), $this->payload(1, [
            'ist_subtest_id' => $other->id, 'kind' => 'example',
        ]))->assertSessionHasNoErrors();
        $this->assertOrder([$b->id]);
        $this->assertOrder([$a->id, $target->id], $other->id, 'example');
        $this->assertSame(1, $this->subtest->fresh()->question_count);
        $this->assertSame(0, $other->fresh()->question_count);
    }

    public function test_existing_gaps_are_repaired_when_editing_and_large_position_appends(): void
    {
        $a = $this->question(1);
        $b = $this->question(6);
        $c = $this->question(8);
        $this->put(route('admin.ist-questions.update', $b), $this->payload(2))->assertSessionHasNoErrors();
        $this->assertOrder([$a->id, $b->id, $c->id]);
        $this->post(route('admin.ist-questions.store'), $this->payload(100))->assertSessionHasNoErrors();
        $new = IstQuestion::where('prompt', 'Question 100')->firstOrFail();
        $this->assertOrder([$a->id, $b->id, $c->id, $new->id]);
    }

    public function test_repair_command_is_read_only_by_default_and_idempotent_when_applied(): void
    {
        $a = $this->question(6);
        $b = $this->question(8);
        $this->artisan('ist:renumber-questions')->assertSuccessful();
        $this->assertSame(6, $a->fresh()->question_number);
        $this->artisan('ist:renumber-questions --apply')->assertSuccessful();
        $this->assertOrder([$a->id, $b->id]);
        $this->artisan('ist:renumber-questions --apply')->expectsOutput('Selesai: 0 soal dirapikan.')->assertSuccessful();
    }

    public function test_content_only_edit_preserves_order_in_a_sparse_bank(): void
    {
        $a = $this->question(1);
        $b = $this->question(6);
        $c = $this->question(8);
        $this->put(route('admin.ist-questions.update', $b), $this->payload(6, ['prompt' => 'Edited']))
            ->assertSessionHasNoErrors();
        $this->assertOrder([$a->id, $b->id, $c->id]);
    }

    public function test_invalid_position_leaves_bank_unchanged(): void
    {
        $a = $this->question(1);
        $b = $this->question(2);
        $this->put(route('admin.ist-questions.update', $b), $this->payload(0))
            ->assertSessionHasErrors('question_number');
        $this->assertOrder([$a->id, $b->id]);
    }

    public function test_database_still_rejects_duplicate_live_numbers(): void
    {
        $this->question(1);
        $this->expectException(QueryException::class);
        $this->question(1);
    }

    public function test_multiple_archived_questions_can_share_a_reused_number(): void
    {
        foreach (range(1, 3) as $attempt) {
            $this->post(route('admin.ist-questions.store'), $this->payload(1))->assertSessionHasNoErrors();
            $row = IstQuestion::where('ist_subtest_id', $this->subtest->id)->firstOrFail();
            $this->delete(route('admin.ist-questions.destroy', $row))->assertSessionHasNoErrors();
        }
        $this->assertSame(3, IstQuestion::onlyTrashed()->where('ist_subtest_id', $this->subtest->id)->count());
    }

    public function test_existing_participant_snapshots_survive_reordering_and_deletion(): void
    {
        $a = $this->question(1);
        $b = $this->question(2);
        $test = IstTest::create([
            'public_id' => (string) Str::uuid(), 'participant_name' => 'Snapshot test',
            'age' => 25, 'gender' => 'M',
        ]);
        $runtime = IstTestSubtest::create([
            'ist_test_id' => $test->id, 'ist_subtest_id' => $this->subtest->id,
            'sequence' => 1, 'question_count' => 2,
        ]);
        $snapshots = app(IstQuestionSnapshotService::class)->snapshot($runtime);
        $answer = $snapshots->first()->answer()->create([
            'numeric_answer' => 4, 'awarded_score' => 1, 'outcome' => 'correct',
        ]);
        $answerBefore = $answer->fresh()->getAttributes();
        $before = $snapshots->map->getAttributes()->all();
        $this->put(route('admin.ist-questions.update', $b), $this->payload(1, ['numeric_answer_key' => 99]))
            ->assertSessionHasNoErrors();
        $this->delete(route('admin.ist-questions.destroy', $a))->assertSessionHasNoErrors();
        $after = $runtime->testQuestions()->orderBy('display_order')->get()->map->getAttributes()->all();
        $this->assertSame($before, $after);
        $this->assertSame($answerBefore, $answer->fresh()->getAttributes());
    }
}
