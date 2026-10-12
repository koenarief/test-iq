<?php

namespace Tests\Feature\Scoring;

use App\Models\CompetencyAnswer;
use App\Models\CompetencyCategory;
use App\Models\CompetencyQuestion;
use App\Models\CompetencyTest;
use App\Services\Scoring\JobFitScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Job Fit Score = nilai akhir Tes Competency (prosentasi-kompetensi.docx),
 * dengan breakdown per sub-kompetensi (kategori) dibobot rata 100% / jumlah
 * kategori, sekadar untuk tampilan di Rekap Kandidat.
 */
class JobFitScoringServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_fit_score_equals_competency_final_score(): void
    {
        $salesOrientation = CompetencyCategory::create(['department' => 'sales', 'name' => 'SALES ORIENTATION', 'order' => 1]);
        $communication = CompetencyCategory::create(['department' => 'sales', 'name' => 'COMMUNICATION', 'order' => 2]);
        $problemSolving = CompetencyCategory::create(['department' => 'sales', 'name' => 'PROBLEM SOLVING', 'order' => 3]);

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

        $jobFit = app(JobFitScoringService::class)->score($competencyTest);

        // Job Fit Score = competency_tests.total_score (4) / 5 * 100 = 80.
        $this->assertSame(80.0, $jobFit['score']);
        $this->assertCount(3, $jobFit['breakdown']);

        $weight = round(100 / 3, 2);
        $this->assertSame([
            ['label' => 'SALES ORIENTATION', 'weight' => $weight, 'value' => 100.0, 'contribution' => round(100 * $weight / 100, 2)],
            ['label' => 'COMMUNICATION', 'weight' => $weight, 'value' => 80.0, 'contribution' => round(80 * $weight / 100, 2)],
            ['label' => 'PROBLEM SOLVING', 'weight' => $weight, 'value' => 60.0, 'contribution' => round(60 * $weight / 100, 2)],
        ], $jobFit['breakdown']);
    }
}
