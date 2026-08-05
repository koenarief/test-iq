<?php

namespace Tests\Feature\Ist\Http;

use App\Models\Ist\IstTest;
use Illuminate\Support\Facades\Route;

class IstSessionOwnershipHttpTest extends IstHttpTestCase
{
    public function test_valid_age_one_hundred_creates_atomic_session_regenerates_id_and_hides_token(): void
    {
        $this->createQuestionBank();
        $session = $this->app['session']->driver();
        $session->start();
        $oldSessionId = $session->getId();

        $response = $this->post(route('ist.start'), [
            'participant_name' => '  HTTP Start  ',
            'age' => 100,
            'gender' => 'P',
        ]);

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
        $this->assertSame(104, $test->subtests()->withCount('testQuestions')->get()->sum('test_questions_count'));
        $this->assertStringNotContainsString($token, $response->getContent());
        $this->assertStringNotContainsString($token, (string) $response->headers->get('Location'));
    }

    public function test_age_101_and_invalid_biodata_or_foreign_fields_are_rejected(): void
    {
        $this->postJson(route('ist.start'), [
            'participant_name' => 'Age', 'age' => 101, 'gender' => 'L',
        ])->assertStatus(422);
        $this->postJson(route('ist.start'), [
            'participant_name' => '', 'age' => 20, 'gender' => 'L',
        ])->assertStatus(422);
        $this->postJson(route('ist.start'), [
            'participant_name' => 'Gender', 'age' => 20, 'gender' => 'X',
        ])->assertStatus(422);
        $this->postJson(route('ist.start'), [
            'participant_name' => 'Foreign', 'age' => 20, 'gender' => 'L', 'score' => 99,
        ])->assertStatus(422);

        $this->assertDatabaseCount('ist_tests', 0);
    }

    public function test_snapshot_failure_rolls_back_test_all_runtimes_and_session_token(): void
    {
        $this->createQuestionBank(1);

        $this->post(route('ist.start'), [
            'participant_name' => 'Incomplete Bank',
            'age' => 25,
            'gender' => 'L',
        ])->assertStatus(409);

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

    public function test_landing_disc_routes_and_inactive_ist_card_remain_unchanged(): void
    {
        $this->assertTrue(Route::has('landing'));
        $this->assertTrue(Route::has('disc.index'));
        $this->assertTrue(Route::has('ist.index'));
        $this->assertTrue(Route::has('ist.start'));
        $this->assertTrue(Route::has('ist.resume'));
        $this->assertSame('disc', Route::getRoutes()->getByName('disc.index')->uri());
        $this->assertSame('ist', Route::getRoutes()->getByName('ist.index')->uri());

        $landing = file_get_contents(resource_path('js/Pages/Landing/Index.jsx'));
        $this->assertIsString($landing);
        $this->assertStringContainsString('title="DISC Personality Test"', $landing);
        $this->assertStringContainsString('title="Tes Kemampuan Kognitif Adaptasi"', $landing);
        $this->assertStringContainsString('Durasi: Sekitar 45 menit', $landing);
        $this->assertMatchesRegularExpression(
            '/title="Tes Kemampuan Kognitif Adaptasi"[\s\S]*?isActive=\{false\}/',
            $landing,
        );
        $this->assertMatchesRegularExpression(
            '/title="DISC Personality Test"[\s\S]*?isActive=\{true\}/',
            $landing,
        );
    }
}
