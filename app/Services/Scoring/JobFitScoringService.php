<?php

namespace App\Services\Scoring;

use App\Models\CompetencyTest;

/**
 * Job Fit Score = nilai akhir Tes Competency kandidat, sesuai
 * prosentasi-kompetensi.docx: tiap sub-kompetensi (kategori) dibobot rata
 * (100% / jumlah kategori), dan Nilai Akhir = Total Skor seluruh soal yang
 * dijawab (skala 0-100) — identik dengan `AssessmentScoringService::scoreCompetency()`.
 *
 * Breakdown-nya memecah nilai itu per sub-kompetensi (kategori) hanya untuk
 * keperluan tampilan di Rekap Kandidat, bukan untuk menghitung ulang skor.
 */
class JobFitScoringService
{
    /**
     * @return array{score: float, breakdown: array<int, array{label: string, weight: float, value: float, contribution: float}>}
     */
    public function score(CompetencyTest $competencyTest): array
    {
        $categoryRows = $competencyTest->answers()
            ->join('competency_categories', 'competency_categories.id', '=', 'competency_answers.competency_category_id')
            ->selectRaw('competency_categories.id as category_id, competency_categories.name as category_name, AVG(competency_answers.points) as avg_points')
            ->groupBy('competency_categories.id', 'competency_categories.name', 'competency_categories.order')
            ->orderBy('competency_categories.order')
            ->get();

        $categoryCount = $categoryRows->count();
        $weight = $categoryCount > 0 ? round(100 / $categoryCount, 2) : 0.0;

        $breakdown = $categoryRows->map(function ($row) use ($weight) {
            $value = round(((float) $row->avg_points / 5) * 100, 2);

            return [
                'label' => $row->category_name,
                'weight' => $weight,
                'value' => $value,
                'contribution' => round($value * $weight / 100, 2),
            ];
        })->all();

        return [
            'score' => AssessmentScoringService::scoreCompetency($competencyTest),
            'breakdown' => $breakdown,
        ];
    }
}
