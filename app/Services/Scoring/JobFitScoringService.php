<?php

namespace App\Services\Scoring;

use App\Models\CandidateAssessment;
use App\Models\CompetencyTest;
use App\Models\DiscTest;
use App\Models\Ist\IstTest;
use App\Models\Position;

/**
 * Job Fit Score = Sum(Competency category value x weight) + (DISC score x
 * DISC weight) + (IST score x IST weight), weights defined per Position.
 *
 * The DISC/IST line items reuse the same single 0-100 "profile strength"
 * and "average subtest percentage" numbers computed by
 * AssessmentScoringService, rather than a per-dimension/per-subtest value —
 * score.md's example lists "DISC = 15%" and "Cognitive/IST = 15%" as flat
 * line items alongside competency sub-dimensions, with no indication which
 * specific dimension should matter per position.
 */
class JobFitScoringService
{
    /**
     * @return array{score: float, breakdown: array<int, array{label: string, weight: float, value: float, contribution: float}>}
     */
    public function score(
        CandidateAssessment $assessment,
        CompetencyTest $competencyTest,
        DiscTest $discTest,
        IstTest $istTest,
    ): array {
        /** @var Position $position */
        $position = Position::query()
            ->with('competencyCategoryWeights.category')
            ->findOrFail($assessment->position_id);

        $categoryPercentages = $competencyTest->answers()
            ->selectRaw('competency_category_id, AVG(points) as avg_points')
            ->groupBy('competency_category_id')
            ->pluck('avg_points', 'competency_category_id');

        $breakdown = [];
        $total = 0.0;

        foreach ($position->competencyCategoryWeights as $categoryWeight) {
            $avgPoints = (float) ($categoryPercentages[$categoryWeight->competency_category_id] ?? 0);
            $value = round(($avgPoints / 5) * 100, 2);
            $weight = (float) $categoryWeight->weight;
            $contribution = round($value * $weight / 100, 2);

            $breakdown[] = [
                'label' => $categoryWeight->category->name,
                'weight' => $weight,
                'value' => $value,
                'contribution' => $contribution,
            ];

            $total += $contribution;
        }

        $discValue = AssessmentScoringService::scoreDisc($discTest);
        $discWeight = (float) $position->disc_weight;
        $discContribution = round($discValue * $discWeight / 100, 2);
        $breakdown[] = [
            'label' => 'DISC',
            'weight' => $discWeight,
            'value' => $discValue,
            'contribution' => $discContribution,
        ];
        $total += $discContribution;

        $istValue = AssessmentScoringService::scoreIst($istTest);
        $istWeight = (float) $position->ist_weight;
        $istContribution = round($istValue * $istWeight / 100, 2);
        $breakdown[] = [
            'label' => 'Cognitive/IST',
            'weight' => $istWeight,
            'value' => $istValue,
            'contribution' => $istContribution,
        ];
        $total += $istContribution;

        return [
            'score' => round($total, 2),
            'breakdown' => $breakdown,
        ];
    }
}
