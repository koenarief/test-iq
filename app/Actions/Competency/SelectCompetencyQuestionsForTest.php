<?php

namespace App\Actions\Competency;

use App\Models\CompetencyCategory;
use App\Models\CompetencyTest;
use App\Models\CompetencyTestQuestion;

/**
 * Setiap divisi punya jumlah subtes (kategori) yang berbeda (3, 4, atau 5),
 * tapi seluruh Tes Kompetensi tetap dialokasikan waktu yang sama: 30 menit,
 * dengan asumsi 1 soal = 1 menit. Supaya totalnya tetap genap sesuai
 * `competency.total_questions`, jumlah soal per subtes dibagi serata
 * mungkin, dan subtes-subtes pertama (secara urutan) mendapat kelebihan
 * bila total tidak habis dibagi rata.
 */
class SelectCompetencyQuestionsForTest
{
    public function handle(CompetencyTest $test): void
    {
        $categories = CompetencyCategory::query()
            ->where('department', $test->department)
            ->orderBy('order')
            ->withCount('questions')
            ->get();

        $totalQuestions = (int) config('competency.total_questions');
        $categoryCount = $categories->count();

        if ($categoryCount === 0) {
            return;
        }

        $base = intdiv($totalQuestions, $categoryCount);
        $remainder = $totalQuestions % $categoryCount;

        $order = 1;

        foreach ($categories as $index => $category) {
            $quota = $base + ($index < $remainder ? 1 : 0);
            $quota = min($quota, $category->questions_count);

            $questionIds = $category->questions()
                ->inRandomOrder()
                ->limit($quota)
                ->pluck('id');

            foreach ($questionIds as $questionId) {
                CompetencyTestQuestion::create([
                    'competency_test_id' => $test->id,
                    'competency_question_id' => $questionId,
                    'competency_category_id' => $category->id,
                    'order' => $order,
                ]);

                $order++;
            }
        }
    }
}
