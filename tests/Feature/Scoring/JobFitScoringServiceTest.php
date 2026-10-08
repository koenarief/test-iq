<?php

namespace Tests\Feature\Scoring;

use App\Models\CompetencyAnswer;
use App\Models\CompetencyCategory;
use App\Models\CompetencyQuestion;
use App\Models\CompetencyTest;
use App\Models\DiscTest;
use App\Models\Ist\IstSubtest;
use App\Models\Ist\IstTest;
use App\Models\Position;
use App\Services\Scoring\JobFitScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Reproduces score.md's Sales Executive example end-to-end against real
 * database records, to lock in the Job Fit Score formula and the house
 * decision (documented in AssessmentScoringService/JobFitScoringService)
 * that the DISC/IST line items use the same single profile-strength /
 * average-subtest-percentage numbers as the Assessment Score.
 */
class JobFitScoringServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_fit_score_matches_score_md_sales_executive_example(): void
    {
        $salesOrientation = CompetencyCategory::create(['department' => 'sales', 'name' => 'SALES ORIENTATION', 'order' => 1]);
        $communication = CompetencyCategory::create(['department' => 'sales', 'name' => 'COMMUNICATION', 'order' => 2]);
        $problemSolving = CompetencyCategory::create(['department' => 'sales', 'name' => 'PROBLEM SOLVING', 'order' => 3]);

        $position = Position::create([
            'name' => 'Sales Executive',
            'department' => 'sales',
            'disc_weight' => 15,
            'ist_weight' => 15,
            'is_active' => true,
        ]);
        $position->competencyCategoryWeights()->createMany([
            ['competency_category_id' => $salesOrientation->id, 'weight' => 30],
            ['competency_category_id' => $communication->id, 'weight' => 25],
            ['competency_category_id' => $problemSolving->id, 'weight' => 15],
        ]);

        $competencyTest = CompetencyTest::create([
            'participant_name' => 'Kandidat Sales',
            'age' => 28,
            'gender' => 'L',
            'department' => 'sales',
            'status' => 'completed',
            'total_score' => 4,
        ]);
        $salesQuestion = CompetencyQuestion::create(['competency_category_id' => $salesOrientation->id, 'question_number' => 1, 'question_text' => 'Q1']);
        $communicationQuestion = CompetencyQuestion::create(['competency_category_id' => $communication->id, 'question_number' => 1, 'question_text' => 'Q1']);
        $problemSolvingQuestion = CompetencyQuestion::create(['competency_category_id' => $problemSolving->id, 'question_number' => 1, 'question_text' => 'Q1']);

        // 5.0 points = 100%, 4.0 points = 80%, 3.0 points = 60%
        CompetencyAnswer::create(['competency_test_id' => $competencyTest->id, 'competency_question_id' => $salesQuestion->id, 'competency_category_id' => $salesOrientation->id, 'selected_label' => 'A', 'points' => 5]);
        CompetencyAnswer::create(['competency_test_id' => $competencyTest->id, 'competency_question_id' => $communicationQuestion->id, 'competency_category_id' => $communication->id, 'selected_label' => 'A', 'points' => 4]);
        CompetencyAnswer::create(['competency_test_id' => $competencyTest->id, 'competency_question_id' => $problemSolvingQuestion->id, 'competency_category_id' => $problemSolving->id, 'selected_label' => 'A', 'points' => 3]);

        $discTest = DiscTest::create([
            'participant_name' => 'Kandidat Sales',
            'age' => 28,
            'gender' => 'L',
            'status' => 'completed',
            'graph_d' => 80,
            'graph_i' => 60,
            'graph_s' => 40,
            'graph_c' => 30,
        ]);

        $istSubtest = IstSubtest::create([
            'code' => 'SE',
            'name' => 'Satuan',
            'sequence' => 1,
            'question_count' => 1,
            'default_answer_type' => 'single_choice',
            'duration_seconds' => 60,
            'memorization_seconds' => 0,
            'answering_seconds' => 60,
            'is_active' => true,
        ]);

        $istTest = IstTest::create([
            'public_id' => (string) \Illuminate\Support\Str::uuid(),
            'access_token_hash' => hash('sha256', 'test-token'),
            'participant_name' => 'Kandidat Sales',
            'age' => 28,
            'gender' => 'L',
            'status' => IstTest::STATUS_COMPLETED,
            'current_subtest_sequence' => 1,
        ]);
        $istTest->subtests()->create([
            'ist_subtest_id' => $istSubtest->id,
            'sequence' => 1,
            'status' => 'completed',
            'question_count' => 1,
            'percentage' => 70,
        ]);

        $assessment = new \App\Models\CandidateAssessment(['position_id' => $position->id]);

        $jobFit = app(JobFitScoringService::class)->score($assessment, $competencyTest, $discTest, $istTest);

        // Sales Orientation: 100% * 30% = 30
        // Communication:      80% * 25% = 20
        // Problem Solving:    60% * 15% = 9
        // DISC:               80  * 15% = 12
        // IST:                70  * 15% = 10.5
        // Total = 81.5
        $this->assertSame(81.5, $jobFit['score']);
        $this->assertCount(5, $jobFit['breakdown']);
    }
}
