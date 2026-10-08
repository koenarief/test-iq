<?php

namespace App\Services\Scoring;

/**
 * Interview Score = Sum(aspect score x weight), fixed weights per score.md's
 * example. Each aspect score is entered manually by HR on a 0-100 scale.
 */
class InterviewScoringService
{
    public const WEIGHTS = [
        'communication' => 0.20,
        'motivation' => 0.15,
        'problem_solving' => 0.20,
        'job_knowledge' => 0.20,
        'attitude' => 0.15,
        'work_alignment' => 0.10,
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
