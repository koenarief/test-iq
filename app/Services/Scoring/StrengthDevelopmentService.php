<?php

namespace App\Services\Scoring;

use App\Models\CompetencyTest;
use App\Models\DiscTest;
use App\Models\Ist\IstTest;

/**
 * score.md's Strength & Development Area bands, applied per competency
 * category, per DISC dimension, and per IST subtest so the recap can show a
 * full breakdown of where a candidate is strong or needs development.
 */
class StrengthDevelopmentService
{
    public function band(float $score): string
    {
        return match (true) {
            $score >= 90 => 'Exceptional',
            $score >= 80 => 'Strong',
            $score >= 70 => 'Adequate',
            $score >= 60 => 'Development Area',
            default => 'Weakness',
        };
    }

    /**
     * @return array<int, array{type: string, key: string, label: string, score: float, band: string}>
     */
    public function build(CompetencyTest $competencyTest, DiscTest $discTest, IstTest $istTest): array
    {
        $breakdown = [];

        $categoryRows = $competencyTest->answers()
            ->join('competency_categories', 'competency_categories.id', '=', 'competency_answers.competency_category_id')
            ->selectRaw('competency_categories.id as category_id, competency_categories.name as category_name, AVG(competency_answers.points) as avg_points')
            ->groupBy('competency_categories.id', 'competency_categories.name', 'competency_categories.order')
            ->orderBy('competency_categories.order')
            ->get();

        foreach ($categoryRows as $row) {
            $score = round(((float) $row->avg_points / 5) * 100, 2);

            $breakdown[] = [
                'type' => 'competency',
                'key' => (string) $row->category_id,
                'label' => $row->category_name,
                'score' => $score,
                'band' => $this->band($score),
            ];
        }

        $discDimensions = [
            'D' => ['label' => 'Dominance', 'score' => (float) $discTest->graph_d],
            'I' => ['label' => 'Influence', 'score' => (float) $discTest->graph_i],
            'S' => ['label' => 'Steadiness', 'score' => (float) $discTest->graph_s],
            'C' => ['label' => 'Compliance', 'score' => (float) $discTest->graph_c],
        ];

        foreach ($discDimensions as $key => $dimension) {
            $breakdown[] = [
                'type' => 'disc',
                'key' => $key,
                'label' => $dimension['label'],
                'score' => $dimension['score'],
                'band' => $this->band($dimension['score']),
            ];
        }

        $subtestRows = $istTest->subtests()->with('subtest')->orderBy('sequence')->get();

        foreach ($subtestRows as $subtestRow) {
            $score = (float) $subtestRow->percentage;

            $breakdown[] = [
                'type' => 'ist',
                'key' => $subtestRow->subtest?->code ?? (string) $subtestRow->id,
                'label' => $subtestRow->subtest?->name ?? 'Subtest',
                'score' => $score,
                'band' => $this->band($score),
            ];
        }

        return $breakdown;
    }
}
