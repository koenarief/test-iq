<?php

namespace App\Services\Scoring;

use App\Models\CompetencyTest;
use App\Models\Position;

/**
 * Job Fit Score = Σ(skor subtes Competency × bobot subtes), per konsep-hrd.pdf.
 * Bobot subtes (per kategori Competency) dikonfigurasi admin per posisi via
 * `position_competency_category_weights` dan wajib berjumlah 100%.
 *
 * Kategori pada tes yang tidak punya baris bobot di posisi tersebut
 * berkontribusi 0 — admin yang mengatur bobot, sistem tidak menebak.
 *
 * `breakdown` yang dikembalikan BUKAN sekadar tampilan: ia adalah snapshot
 * bobot yang dipakai saat skor ini dihitung (position_id/name + tiap baris),
 * disimpan apa adanya ke `candidate_assessments.job_fit_breakdown` supaya
 * perubahan bobot posisi di kemudian hari tidak mengubah skor kandidat lama
 * secara retroaktif.
 */
class JobFitScoringService
{
    /**
     * @return array{score: float, breakdown: array{position_id: int, position_name: string, rows: array<int, array{category_id: int, label: string, weight: float, value: float, contribution: float}>}}
     */
    public function score(CompetencyTest $competencyTest, Position $position): array
    {
        $categoryRows = $competencyTest->answers()
            ->join('competency_categories', 'competency_categories.id', '=', 'competency_answers.competency_category_id')
            ->selectRaw('competency_categories.id as category_id, competency_categories.name as category_name, AVG(competency_answers.points) as avg_points')
            ->groupBy('competency_categories.id', 'competency_categories.name', 'competency_categories.order')
            ->orderBy('competency_categories.order')
            ->get();

        $weightsByCategory = $position->competencyCategoryWeights->pluck('weight', 'competency_category_id');

        $rows = $categoryRows->map(function ($row) use ($weightsByCategory) {
            $weight = (float) ($weightsByCategory->get($row->category_id) ?? 0.0);
            $value = round(((float) $row->avg_points / 5) * 100, 3);
            $contribution = round($value * $weight / 100, 3);

            return [
                'category_id' => (int) $row->category_id,
                'label' => $row->category_name,
                'weight' => $weight,
                'value' => $value,
                'contribution' => $contribution,
            ];
        })->values()->all();

        $score = round(array_sum(array_column($rows, 'contribution')), 2);

        return [
            'score' => $score,
            'breakdown' => [
                'position_id' => $position->id,
                'position_name' => $position->name,
                'rows' => $rows,
            ],
        ];
    }
}
