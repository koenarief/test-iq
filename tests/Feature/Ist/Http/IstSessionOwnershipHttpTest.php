<?php

namespace Tests\Feature\Ist\Http;

use App\Models\DiscTest;
use App\Models\Ist\IstTest;
use App\Models\User;
use Illuminate\Support\Facades\Route;

class IstSessionOwnershipHttpTest extends IstHttpTestCase
{
    /**
     * The merged assessment flow validates biodata once, at
     * `assessment.begin`, and only reaches `assessment.continue.ist` once a
     * candidate has a completed DISC test. This mirrors that precondition
     * without driving the actual DISC HTTP flow, since that is not what
     * these tests are about.
     */
    private function candidateReadyForIst(string $name, int $age, string $gender): User
    {
        $candidate = User::factory()->create([
            'role' => User::ROLE_CANDIDATE,
            'status' => User::STATUS_AKTIF,
            'name' => $name,
        ]);

        DiscTest::create([
            'user_id' => $candidate->id,
            'participant_name' => $name,
            'age' => $age,
            'gender' => $gender,
            'status' => 'completed',
        ]);

        $this->actingAs($candidate);
        $this->withSession(['assessment.biodata' => ['age' => $age, 'gender' => $gender]]);

        return $candidate;
    }

    public function test_continue_to_ist_creates_atomic_session_regenerates_id_and_hides_token(): void
    {
        $this->createQuestionBank();
        $this->candidateReadyForIst('  HTTP Start  ', 100, 'P');

        $session = $this->app['session']->driver();
        $session->start();
        $oldSessionId = $session->getId();

        $response = $this->post(route('assessment.continue.ist'));

        $test = IstTest::query()->sole();
        $newSessionId = $this->app['session']->driver()->getId();
        $token = session($this->sessionKey($test));

        $response->assertStatus(303)
            ->assertRedirect(route('ist.resume', ['test' => $test->public_id]));
        $this->assertNotSame($oldSessionId, $newSessionId);
        $this->assertIsString($token);
        $this->assertTrue(hash_equals($test->access_token_hash, hash('sha256', $token)));
        $this->assertSame('HTTP Start', $test->participant_name);
        $this->assertSame(9, $test->subtests()->count());
        $this->assertSame(176, $test->subtests()->withCount('testQuestions')->get()->sum('test_questions_count'));
        $this->assertStringNotContainsString($token, $response->getContent());
        $this->assertStringNotContainsString($token, (string) $response->headers->get('Location'));
    }

    public function test_continue_to_ist_requires_a_completed_disc_test_first(): void
    {
        $candidate = User::factory()->create([
            'role' => User::ROLE_CANDIDATE,
            'status' => User::STATUS_AKTIF,
        ]);

        DiscTest::create([
            'user_id' => $candidate->id,
            'participant_name' => $candidate->name,
            'age' => 25,
            'gender' => 'L',
            'status' => 'in_progress',
        ]);

        $discTest = DiscTest::where('user_id', $candidate->id)->firstOrFail();

        $this->actingAs($candidate)
            ->withSession(['assessment.biodata' => ['age' => 25, 'gender' => 'L']])
            ->post(route('assessment.continue.ist'))
            ->assertRedirect(route('disc.instruction', $discTest->id));

        $this->assertDatabaseCount('ist_tests', 0);
    }

    public function test_snapshot_failure_rolls_back_test_all_runtimes_and_session_token(): void
    {
        $this->createQuestionBank(1);
        $this->candidateReadyForIst('Incomplete Bank', 25, 'L');

        $this->post(route('assessment.continue.ist'))->assertStatus(409);

        $this->assertDatabaseCount('ist_tests', 0);
        $this->assertDatabaseCount('ist_test_subtests', 0);
        $this->assertDatabaseCount('ist_test_questions', 0);
        $this->assertNull(session('ist.access_tokens'));
    }

    public function test_correct_token_can_resume_while_missing_wrong_and_other_test_tokens_are_hidden(): void
    {
        [$test] = $this->createOwnedTest('Owner A');

        $this->get(route('ist.resume', ['test' => $test->public_id]))
            ->assertStatus(303)
            ->assertRedirect(route('ist.subtests.instruction', [
                'test' => $test->public_id,
                'subtest' => 'SE',
            ]));

        session()->flush();
        $this->get(route('ist.resume', ['test' => $test->public_id]))
            ->assertNotFound();

        $this->withSession([
            'ist' => ['access_tokens' => [$test->public_id => 'wrong-token']],
        ])->get(route('ist.resume', ['test' => $test->public_id]))
            ->assertNotFound();

        [$other] = $this->createOwnedTest('Owner B');
        $this->withSession([
            'ist' => ['access_tokens' => [$other->public_id => session($this->sessionKey($other))]],
        ])->get(route('ist.resume', ['test' => $test->public_id]))
            ->assertNotFound();
    }

    public function test_integer_database_id_cannot_replace_public_id(): void
    {
        [$test] = $this->createOwnedTest();

        $this->get("/ist/{$test->id}/resume")->assertNotFound();
    }

    public function test_landing_and_merged_assessment_routes_remain_available(): void
    {
        $this->assertTrue(Route::has('landing'));
        $this->assertTrue(Route::has('assessment.start'));
        $this->assertTrue(Route::has('assessment.begin'));
        $this->assertTrue(Route::has('assessment.continue.ist'));
        $this->assertTrue(Route::has('assessment.continue.competency'));
        $this->assertTrue(Route::has('ist.resume'));
        $this->assertFalse(Route::has('ist.index'));
        $this->assertFalse(Route::has('ist.start'));
        $this->assertFalse(Route::has('disc.index'));

        $landing = file_get_contents(resource_path('js/Pages/Landing/Index.jsx'));
        $this->assertIsString($landing);
        $this->assertStringContainsString("route('assessment.start')", $landing);
    }
}
