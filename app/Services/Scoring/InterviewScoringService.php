<?php

namespace App\Services\Scoring;

/**
 * INTERVIEW = (Skor HRD x 40%) + (Skor User x 60%), per konsep-hrd.pdf §7.
 * Skor HRD/User itu sendiri datang dari InterviewHrdScoringService /
 * InterviewUserScoringService — kelas ini hanya menggabungkan keduanya
 * setelah kedua tahap selesai.
 */
class InterviewScoringService
{
    public const HRD_WEIGHT = 0.40;

    public const USER_WEIGHT = 0.60;

    public function combine(float $hrdScore, float $userScore): float
    {
        return round($hrdScore * self::HRD_WEIGHT + $userScore * self::USER_WEIGHT, 2);
    }
}
