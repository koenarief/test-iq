<?php

namespace App\Services\Scoring;

/**
 * Interview User Score = Σ(skor aspek x bobot aspek), per konsep-hrd.pdf §6.
 * Fokus: kemampuan, pemahaman pekerjaan, dan kesiapan kandidat menjalankan
 * tugas sesuai posisi. Tiap aspek dinilai user/owner berwenang 1-100.
 */
class InterviewUserScoringService
{
    public const WEIGHTS = [
        'task_understanding' => 0.20,
        'work_competence' => 0.25,
        'problem_analysis' => 0.15,
        'work_quality' => 0.15,
        'initiative_independence' => 0.10,
        'communication_teamwork' => 0.15,
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
