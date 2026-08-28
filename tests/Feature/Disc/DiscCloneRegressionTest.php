<?php

namespace Tests\Feature\Disc;

use App\Models\DiscGraphConversion;
use App\Models\DiscQuestion;
use App\Models\DiscTest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class DiscCloneRegressionTest extends TestCase
{
    private const CLONE_DATABASE = 'tes_iq_migration_test';

    private bool $transactionStarted = false;

    protected function setUp(): void
    {
        parent::setUp();

        if (! app()->environment('local')
            || config('database.default') !== 'mysql'
            || config('database.connections.mysql.database') !== self::CLONE_DATABASE) {
            $this->markTestSkipped('Stage 18 DISC regression hanya boleh dijalankan pada clone local.');
        }

        $activeDatabase = DB::selectOne('select database() as active_database')->active_database ?? null;

        if ($activeDatabase !== self::CLONE_DATABASE || $activeDatabase === 'tes_iq') {
            throw new \RuntimeException('Stage 18 DISC regression guard menolak database aktif.');
        }

        DB::beginTransaction();
        $this->transactionStarted = true;
        $this->withoutVite();
    }

    protected function tearDown(): void
    {
        if ($this->transactionStarted && DB::transactionLevel() > 0) {
            DB::rollBack();
        }

        parent::tearDown();
    }

    public function test_landing_routes_and_active_question_bank_remain_available(): void
    {
        $this->assertTrue(Route::has('landing'));
        $this->assertTrue(Route::has('disc.index'));
        $this->assertTrue(Route::has('disc.start'));
        $this->assertTrue(Route::has('disc.test'));
        $this->assertTrue(Route::has('disc.submit'));
        $this->assertTrue(Route::has('disc.result'));
        $this->assertSame(24, DiscQuestion::query()->where('is_active', true)->count());

        $this->get(route('landing'))->assertOk();
        $this->get(route('disc.index'))->assertOk();
    }

    public function test_disc_creation_exact_answer_validation_persistence_scoring_and_result_are_deterministic(): void
    {
        $existingTestCount = DiscTest::query()->count();
        $existingAnswerCount = DB::table('disc_answers')->count();

        $start = $this->post(route('disc.start'), [
            'participant_name' => 'Stage 18 Clone Regression',
            'age' => 30,
            'gender' => 'L',
        ]);

        $created = DiscTest::query()->latest('id')->firstOrFail();
        $start->assertRedirect(route('disc.instruction', $created));
        $this->assertSame($existingTestCount + 1, DiscTest::query()->count());
        $this->get(route('disc.instruction', $created))->assertOk();
        $this->get(route('disc.test', $created))->assertOk();

        $questions = DiscQuestion::query()
            ->where('is_active', true)
            ->orderBy('question_number')
            ->get();
        $this->assertCount(24, $questions);

        $invalidAnswers = $questions->take(23)->mapWithKeys(
            fn (DiscQuestion $question): array => [$question->id => [
                'most_choice' => 1,
                'least_choice' => 2,
            ]],
        )->all();

        $this->from(route('disc.test', $created))
            ->post(route('disc.submit', $created), ['answers' => $invalidAnswers])
            ->assertSessionHasErrors('answers');
        $this->assertSame(0, $created->answers()->count());

        $answers = $questions->mapWithKeys(
            fn (DiscQuestion $question, int $index): array => [$question->id => [
                'most_choice' => ($index % 4) + 1,
                'least_choice' => (($index + 1) % 4) + 1,
            ]],
        )->all();
        [$most, $least, $change, $graph, $primary, $secondary, $discType] =
            $this->expectedScores($questions, $answers);

        $this->post(route('disc.submit', $created), ['answers' => $answers])
            ->assertRedirect(route('disc.result', $created));

        $created->refresh();
        $this->assertSame('completed', $created->status);
        $this->assertSame(24, $created->answers()->count());
        $this->assertSame($existingAnswerCount + 24, DB::table('disc_answers')->count());

        foreach (['D', 'I', 'S', 'C'] as $dimension) {
            $column = strtolower($dimension);
            $this->assertSame($most[$dimension], $created->{"most_{$column}"});
            $this->assertSame($least[$dimension], $created->{"least_{$column}"});
            $this->assertSame($change[$dimension], $created->{"change_{$column}"});
            $this->assertSame($graph[$dimension], $created->{"graph_{$column}"});
        }

        $this->assertSame($primary, $created->primary_type);
        $this->assertSame($secondary, $created->secondary_type);
        $this->assertSame($discType, $created->disc_type);
        $this->assertNotNull($created->disc_profile_id);

        $result = $this->withHeader('X-Inertia', 'true')
            ->get(route('disc.result', $created));
        $result->assertOk()
            ->assertJsonPath('component', 'DISC/Result')
            ->assertJsonPath('props.discTest.id', $created->id)
            ->assertJsonPath('props.discTest.disc_type', $discType);
    }

    public function test_deleting_user_only_nulls_disc_test_user_id(): void
    {
        $user = User::query()->create([
            'name' => 'Stage 18 Disposable User',
            'email' => 'stage18-clone-regression@example.invalid',
            'password' => bcrypt('not-a-runtime-secret'),
        ]);
        $discTest = DiscTest::query()->create([
            'user_id' => $user->id,
            'participant_name' => 'Stage 18 User FK',
            'age' => 30,
            'gender' => 'P',
            'status' => 'draft',
        ]);

        $user->delete();

        $this->assertDatabaseHas('disc_tests', [
            'id' => $discTest->id,
            'user_id' => null,
        ]);
    }

    public function test_ist_dry_run_cleanup_does_not_change_any_disc_row(): void
    {
        $before = $this->discSnapshot();
        config()->set('ist.development_database', self::CLONE_DATABASE);

        $this->artisan('ist:remove-development-questions')
            ->expectsOutputToContain('dry-run')
            ->assertSuccessful();

        $this->assertSame($before, $this->discSnapshot());
    }

    public function test_ist_foreign_keys_never_reference_disc_tables(): void
    {
        $references = DB::table('information_schema.key_column_usage')
            ->where('table_schema', self::CLONE_DATABASE)
            ->where('table_name', 'like', 'ist\\_%')
            ->where('referenced_table_name', 'like', 'disc\\_%')
            ->count();

        $this->assertSame(0, $references);
    }

    private function expectedScores($questions, array $answers): array
    {
        $most = array_fill_keys(['D', 'I', 'S', 'C'], 0);
        $least = array_fill_keys(['D', 'I', 'S', 'C'], 0);

        foreach ($questions as $question) {
            $answer = $answers[$question->id];
            $most[$question->{'mapping_'.$answer['most_choice']}]++;
            $least[$question->{'mapping_'.$answer['least_choice']}]++;
        }

        $change = [];
        $graph = [];

        foreach (['D', 'I', 'S', 'C'] as $dimension) {
            $change[$dimension] = $most[$dimension] - $least[$dimension];
            $graph[$dimension] = (int) DiscGraphConversion::query()
                ->where('graph_type', 'change')
                ->where('dimension', $dimension)
                ->where('raw_score', $change[$dimension])
                ->value('graph_score');
        }

        $sorted = $graph;
        arsort($sorted);
        $types = array_keys($sorted);
        $values = array_values($sorted);
        $primary = $types[0];
        $secondary = $types[1];
        $discType = abs($values[0] - $values[1]) <= 5
            ? $primary.$secondary
            : $primary;

        return [$most, $least, $change, $graph, $primary, $secondary, $discType];
    }

    private function discSnapshot(): array
    {
        $snapshot = [];

        foreach ([
            'disc_questions',
            'disc_tests',
            'disc_answers',
            'disc_results',
            'disc_graph_conversions',
            'disc_profiles',
            'disc_statement_interpretations',
        ] as $table) {
            $snapshot[$table] = DB::table($table)
                ->orderBy('id')
                ->get()
                ->map(static fn (object $row): array => (array) $row)
                ->all();
        }

        return $snapshot;
    }
}
