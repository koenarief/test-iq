<?php

namespace App\Services\Scoring;

/**
 * Interview HRD Score = Σ(skor aspek x bobot aspek), per konsep-hrd.pdf §5.
 * Fokus: sikap, motivasi, komitmen, dan kecocokan kandidat dengan
 * perusahaan. Tiap aspek dinilai HRD 1-100.
 */
class InterviewHrdScoringService
{
    public const WEIGHTS = [
        'attitude_ethics' => 0.20,
        'communication' => 0.15,
        'motivation_goals' => 0.15,
        'discipline_responsibility' => 0.20,
        'commitment_readiness' => 0.15,
        'adaptability_teamwork' => 0.15,
    ];

    /**
     * @param  array<string, float>  $aspectScores  keyed by the WEIGHTS keys above
     */
    public function score(array $aspectScores): float
    {
        $total = 0.0;

        foreach (self::WEIGHTS as $aspect => $weight) {
            $total += (float) ($aspectScores[$aspect] ?? 0) * $weight;
        }

        return round($total, 2);
    }
}
