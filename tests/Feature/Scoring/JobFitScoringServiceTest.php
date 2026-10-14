<?php

namespace Tests\Feature\Scoring;

use App\Models\CompetencyAnswer;
use App\Models\CompetencyCategory;
use App\Models\CompetencyQuestion;
use App\Models\CompetencyTest;
use App\Models\Position;
use App\Services\Scoring\JobFitScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Job Fit Score = Σ(skor subtes Competency × bobot subtes per posisi),
 * per konsep-hrd.pdf §4 — bobot datang dari
 * position_competency_category_weights, bukan rata-rata sama.
 */
class JobFitScoringServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_fit_score_is_weighted_sum_using_position_weights(): void
    {
        $salesOrientation = CompetencyCategory::create(['department' => 'sales', 'name' => 'SALES ORIENTATION', 'order' => 1]);
        $communication = CompetencyCategory::create(['department' => 'sales', 'name' => 'COMMUNICATION', 'order' => 2]);
        $problemSolving = CompetencyCategory::create(['department' => 'sales', 'name' => 'PROBLEM SOLVING', 'order' => 3]);

        $position = Position::create(['name' => 'Sales Executive', 'department' => 'sales', 'is_active' => true]);
        $position->competencyCategoryWeights()->createMany([
            ['competency_category_id' => $salesOrientation->id, 'weight' => 50],
            ['competency_category_id' => $communication->id, 'weight' => 30],
            ['competency_category_id' => $problemSolving->id, 'weight' => 20],
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

        $jobFit = app(JobFitScoringService::class)->score($competencyTest, $position);

        // Σ(value x weight/100) = 100*0.50 + 80*0.30 + 60*0.20 = 50 + 24 + 12 = 86
        $this->assertSame(86.0, $jobFit['score']);
        $this->assertSame($position->id, $jobFit['breakdown']['position_id']);
        $this->assertSame('Sales Executive', $jobFit['breakdown']['position_name']);
        $this->assertCount(3, $jobFit['breakdown']['rows']);

        $this->assertSame([
            ['category_id' => $salesOrientation->id, 'label' => 'SALES ORIENTATION', 'weight' => 50.0, 'value' => 100.0, 'contribution' => 50.0],
            ['category_id' => $communication->id, 'label' => 'COMMUNICATION', 'weight' => 30.0, 'value' => 80.0, 'contribution' => 24.0],
            ['category_id' => $problemSolving->id, 'label' => 'PROBLEM SOLVING', 'weight' => 20.0, 'value' => 60.0, 'contribution' => 12.0],
        ], $jobFit['breakdown']['rows']);
    }

    public function test_category_without_configured_weight_contributes_zero(): void
    {
        $category = CompetencyCategory::create(['department' => 'sales', 'name' => 'UNCONFIGURED', 'order' => 1]);
        $position = Position::create(['name' => 'No Weights', 'department' => 'sales', 'is_active' => true]);

        $competencyTest = CompetencyTest::create([
            'participant_name' => 'Kandidat Sales',
            'age' => 28,
            'gender' => 'L',
            'department' => 'sales',
            'status' => 'completed',
            'total_score' => 5,
        ]);
        $question = CompetencyQuestion::create(['competency_category_id' => $category->id, 'question_number' => 1, 'question_text' => 'Q1']);
        CompetencyAnswer::create(['competency_test_id' => $competencyTest->id, 'competency_question_id' => $question->id, 'competency_category_id' => $category->id, 'selected_label' => 'A', 'points' => 5]);

        $jobFit = app(JobFitScoringService::class)->score($competencyTest, $position);

        $this->assertSame(0.0, $jobFit['score']);
        $this->assertSame(0.0, $jobFit['breakdown']['rows'][0]['weight']);
    }
}
