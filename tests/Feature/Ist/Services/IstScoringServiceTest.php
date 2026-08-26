<?php

namespace Tests\Feature\Ist\Services;

use App\Models\IstAnswerKey;
use App\Models\IstNormSubtest;
use App\Models\IstNormTotal;
use App\Models\IstTestSession;
use App\Models\IstUserResponse;
use App\Services\Ist\IstScoringService;
use Illuminate\Database\QueryException;
use Throwable;

class IstScoringServiceTest extends IstDatabaseTestCase
{
    private IstScoringService $service;

    private static int $participantSequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(IstScoringService::class);
    }

    private function createSession(int $age = 20): IstTestSession
    {
        self::$participantSequence++;

        return IstTestSession::create([
            'participant_number' => 'P-' . self::$participantSequence,
            'name' => 'Test Participant',
            'birth_date' => now()->subYears($age)->toDateString(),
            'test_date' => now()->toDateString(),
            'age' => $age,
            'gender' => 'MALE',
        ]);
    }

    private function createAnswerKey(string $subtest, int $questionNumber, string $correctAnswer, int $weight = 1): IstAnswerKey
    {
        return IstAnswerKey::create([
            'subtest' => $subtest,
            'question_number' => $questionNumber,
            'correct_answer' => $correctAnswer,
            'score_weight' => $weight,
        ]);
    }

    private function createNormSubtest(string $subtest, int $rawScore, int $standardScore, int $minAge = 10, int $maxAge = 99): IstNormSubtest
    {
        return IstNormSubtest::create([
            'subtest' => $subtest,
            'raw_score' => $rawScore,
            'standard_score' => $standardScore,
            'min_age' => $minAge,
            'max_age' => $maxAge,
        ]);
    }

    private function createNormTotal(int $totalSw, int $iqScore, string $iqCategory, int $minAge = 10, int $maxAge = 99): IstNormTotal
    {
        return IstNormTotal::create([
            'total_sw' => $totalSw,
            'iq_score' => $iqScore,
            'iq_category' => $iqCategory,
            'min_age' => $minAge,
            'max_age' => $maxAge,
        ]);
    }

    public function test_process_scoring_computes_raw_and_standard_scores_and_persists_responses(): void
    {
        $this->createAnswerKey('SE', 1, 'A');
        $this->createAnswerKey('SE', 2, 'B');
        $this->createAnswerKey('WA', 1, 'budi');
        $this->createAnswerKey('GE', 1, json_encode(['score_2' => ['burung'], 'score_1' => ['terbang']]));
        $this->createAnswerKey('FA', 1, '5');

        $this->createNormSubtest('SE', 1, 95);
        $this->createNormSubtest('WA', 1, 110);
        $this->createNormSubtest('GE', 2, 105);
        $this->createNormSubtest('FA', 1, 90);

        $this->createNormTotal(100, 115, 'Tinggi');

        $session = $this->createSession(age: 20);

        $result = $this->service->processScoring($session, [
            'SE' => [1 => ' a ', 2 => 'C'],
            'WA' => [1 => 'Budi'],
            'GE' => [1 => 'seekor burung besar'],
            'FA' => [1 => '5'],
        ]);

        $this->assertSame(1, $result->rw_se);
        $this->assertSame(1, $result->rw_wa);
        $this->assertSame(2, $result->rw_ge);
        $this->assertSame(1, $result->rw_fa);
        $this->assertSame(0, $result->rw_an);
        $this->assertSame(0, $result->rw_ra);
        $this->assertSame(0, $result->rw_zr);
        $this->assertSame(0, $result->rw_wu);
        $this->assertSame(0, $result->rw_me);
        $this->assertSame(5, $result->total_rw);

        $this->assertSame(95, $result->sw_se);
        $this->assertSame(110, $result->sw_wa);
        $this->assertSame(105, $result->sw_ge);
        $this->assertSame(90, $result->sw_fa);
        $this->assertSame(100, $result->sw_an);
        $this->assertSame(100, $result->sw_ra);
        $this->assertSame(100, $result->sw_zr);
        $this->assertSame(100, $result->sw_wu);
        $this->assertSame(100, $result->sw_me);
        // Total SW is the mean (not sum) of the 9 subtest SW values.
        $this->assertSame(100, $result->total_sw);

        $this->assertSame(115, $result->iq_score);
        $this->assertSame('Tinggi', $result->iq_category);
        $this->assertSame('W-Dominant (Verbal High)', $result->dominance_profile);

        $this->assertSame(5, IstUserResponse::where('test_session_id', $session->id)->count());
        $this->assertDatabaseHas('ist_user_responses', [
            'test_session_id' => $session->id,
            'subtest' => 'SE',
            'question_number' => 1,
            'user_answer' => ' a ',
            'earned_score' => 1,
        ]);
        $this->assertDatabaseHas('ist_user_responses', [
            'test_session_id' => $session->id,
            'subtest' => 'SE',
            'question_number' => 2,
            'user_answer' => 'C',
            'earned_score' => 0,
        ]);
        $this->assertDatabaseHas('ist_user_responses', [
            'test_session_id' => $session->id,
            'subtest' => 'GE',
            'question_number' => 1,
            'earned_score' => 2,
        ]);
    }

    public function test_process_scoring_defaults_to_standard_score_100_and_leaves_iq_null_when_norms_are_missing(): void
    {
        $this->createAnswerKey('SE', 1, 'A');

        $session = $this->createSession(age: 20);

        $result = $this->service->processScoring($session, [
            'SE' => [1 => 'A'],
        ]);

        $this->assertSame(1, $result->rw_se);
        $this->assertSame(100, $result->sw_se);
        $this->assertSame(100, $result->total_sw);
        $this->assertNull($result->iq_score);
        $this->assertNull($result->iq_category);
    }

    public function test_process_scoring_rolls_back_all_responses_when_a_response_write_fails(): void
    {
        $this->createAnswerKey('SE', 1, 'A');

        $session = $this->createSession(age: 20);

        $caught = null;

        try {
            $this->service->processScoring($session, [
                'SE' => [1 => 'A'],
                'ZZ' => [1 => 'anything'],
            ]);
        } catch (Throwable $e) {
            $caught = $e;
        }

        $this->assertInstanceOf(QueryException::class, $caught);
        $this->assertSame(0, IstUserResponse::count());
        $this->assertSame(0, $session->fresh()->total_rw);
        $this->assertSame(0, $session->fresh()->rw_se);
    }

    public function test_calculate_session_score_regrades_existing_responses_and_updates_session(): void
    {
        $session = $this->createSession(age: 15);

        $this->createAnswerKey('SE', 1, 'A');
        $this->createAnswerKey('GE', 1, json_encode(['score_2' => ['merah'], 'score_1' => ['biru']]));

        IstUserResponse::create([
            'test_session_id' => $session->id,
            'subtest' => 'SE',
            'question_number' => 1,
            'user_answer' => 'a',
            'earned_score' => 0,
        ]);
        IstUserResponse::create([
            'test_session_id' => $session->id,
            'subtest' => 'GE',
            'question_number' => 1,
            'user_answer' => 'Merah',
            'earned_score' => 0,
        ]);

        $this->createNormSubtest('SE', 1, 97);
        $this->createNormSubtest('GE', 2, 102);
        // Mean of the 9 subtest SW values (97, 102, and seven fallback 100s)
        // rounds to 100, not their sum (899).
        $this->createNormTotal(100, 108, 'Rata-Rata');

        $result = $this->service->calculateSessionScore($session->id);

        $this->assertSame($session->id, $result['session_id']);
        $this->assertSame(1, $result['subtest_rw']['SE']);
        $this->assertSame(2, $result['subtest_rw']['GE']);
        $this->assertSame(0, $result['subtest_rw']['AN']);
        $this->assertSame(97, $result['subtest_sw']['SE']);
        $this->assertSame(102, $result['subtest_sw']['GE']);
        $this->assertSame(100, $result['subtest_sw']['AN']);
        $this->assertSame(100, $result['total_sw']);
        $this->assertSame(108, $result['iq_score']);
        $this->assertSame('Rata-Rata', $result['iq_category']);
        $this->assertSame('Dominan Verbal / Konseptual', $result['dominance']);

        $this->assertDatabaseHas('ist_user_responses', [
            'test_session_id' => $session->id,
            'subtest' => 'SE',
            'question_number' => 1,
            'earned_score' => 1,
        ]);
        $this->assertDatabaseHas('ist_user_responses', [
            'test_session_id' => $session->id,
            'subtest' => 'GE',
            'question_number' => 1,
            'earned_score' => 2,
        ]);

        $fresh = $session->fresh();
        $this->assertSame(100, $fresh->total_sw);
        $this->assertSame(108, $fresh->iq_score);
        $this->assertSame('Rata-Rata', $fresh->iq_category);
        $this->assertSame('Dominan Verbal / Konseptual', $fresh->dominance_type);

        $subtestScores = json_decode($fresh->subtest_scores, true);
        $this->assertSame(1, $subtestScores['rw']['SE']);
        $this->assertSame(97, $subtestScores['sw']['SE']);
        $this->assertSame('Rata-Rata', $subtestScores['categories']['SE']);
    }

    public function test_calculate_session_score_defaults_iq_to_100_average_when_norm_total_is_missing(): void
    {
        $session = $this->createSession(age: 15);

        $this->createAnswerKey('SE', 1, 'A');
        IstUserResponse::create([
            'test_session_id' => $session->id,
            'subtest' => 'SE',
            'question_number' => 1,
            'user_answer' => 'A',
            'earned_score' => 0,
        ]);

        $result = $this->service->calculateSessionScore($session->id);

        $this->assertSame(100, $result['iq_score']);
        $this->assertSame('Average', $result['iq_category']);
        // Spatial only sums FA+WU+ME (3 subtests) vs SE+WA+AN+GE for verbal (4 subtests),
        // so even uniform SW=100 fallbacks skew verbal-dominant.
        $this->assertSame('Dominan Verbal / Konseptual', $result['dominance']);
    }
}
