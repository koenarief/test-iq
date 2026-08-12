<?php

namespace App\Support\Ist;

use App\Models\Ist\IstAnswer;
use InvalidArgumentException;

final class IstScoreCalculator
{
    private const FINAL_SUBTEST_COUNT = 9;

    private const DIFFICULTY_WEIGHTS = [
        'easy' => 1,
        'medium' => 2,
        'hard' => 3,
    ];

    /**
     * Convert difficulty label into scoring weight.
     */
    public function difficultyWeight(string $difficulty): int
    {
        $difficulty = strtolower(trim($difficulty));

        if (! array_key_exists($difficulty, self::DIFFICULTY_WEIGHTS)) {
            throw new InvalidArgumentException(
                'IST difficulty must be easy, medium, or hard.'
            );
        }

        return self::DIFFICULTY_WEIGHTS[$difficulty];
    }

    /**
     * Binary scoring.
     *
     * Correct = 1 × difficulty weight
     * Wrong   = 0
     * Blank   = 0
     */
    public function scoreBinary(
        ?bool $isCorrect,
        string $difficulty = 'medium',
    ): array {
        $weight = $this->difficultyWeight($difficulty);

        if ($isCorrect === null) {
            return $this->result(
                0,
                IstAnswer::OUTCOME_BLANK,
            );
        }

        if ($isCorrect) {
            return $this->result(
                $weight,
                IstAnswer::OUTCOME_CORRECT,
            );
        }

        return $this->result(
            0,
            IstAnswer::OUTCOME_WRONG,
        );
    }

    /**
     * Final GE scoring:
     *
     * 3   = correct
     * 1-2 = partial
     * 0   = wrong
     * null = blank
     */
    public function scoreWeighted(
        int|float|string|null $scoreValue,
        string $difficulty = 'medium',
    ): array {
        $weight = $this->difficultyWeight($difficulty);

        if ($scoreValue === null || $scoreValue === '') {
            return $this->result(
                0,
                IstAnswer::OUTCOME_BLANK,
            );
        }

        $canonical = $this->canonicalNumber($scoreValue);

        if (! in_array($canonical, ['0', '1', '2', '3'], true)) {
            throw new InvalidArgumentException(
                'Weighted IST score must be an integer from 0 to 3.'
            );
        }

        $baseScore = (int) $canonical;
        $awardedScore = $baseScore * $weight;

        if ($baseScore === 3) {
            return $this->result(
                $awardedScore,
                IstAnswer::OUTCOME_CORRECT,
            );
        }

        if ($baseScore >= 1) {
            return $this->result(
                $awardedScore,
                IstAnswer::OUTCOME_PARTIAL,
            );
        }

        return $this->result(
            0,
            IstAnswer::OUTCOME_WRONG,
        );
    }

    /**
     * Numeric scoring follows binary scoring.
     */
    public function scoreNumeric(
        int|float|string|null $numericAnswer,
        int|float|string $answerKey,
        string $difficulty = 'medium',
    ): array {
        if (
            $numericAnswer === null
            || trim((string) $numericAnswer) === ''
        ) {
            return $this->result(
                0,
                IstAnswer::OUTCOME_BLANK,
            );
        }

        $isCorrect = $this->canonicalNumber($numericAnswer)
            === $this->canonicalNumber($answerKey);

        return $this->scoreBinary(
            $isCorrect,
            $difficulty,
        );
    }

    /**
     * Maximum possible score after difficulty weighting.
     *
     * Examples:
     * binary hard: 1 × 3 = 3
     * GE medium:   3 × 2 = 6
     */
    public function weightedMaxScore(
        int|float|string $baseMaxScore,
        string $difficulty,
    ): float {
        $maximum = (float) $this->canonicalNumber($baseMaxScore);

        if ($maximum < 0.0) {
            throw new InvalidArgumentException(
                'IST maximum score cannot be negative.'
            );
        }

        return $maximum * $this->difficultyWeight($difficulty);
    }

    /**
     * Normalize weighted subtest score into 0-100.
     */
    public function percentage(
        int|float|string $awardedScore,
        int|float|string $maxScore,
    ): float {
        $awarded = (float) $this->canonicalNumber($awardedScore);
        $maximum = (float) $this->canonicalNumber($maxScore);

        if ($maximum <= 0.0) {
            return 0.0;
        }

        return round(
            ($awarded / $maximum) * 100,
            3,
        );
    }

    /**
     * TEMPORARY overall calculation.
     *
     * This still preserves the existing calculation until
     * the four-area + Cognitive Performance Index calculation
     * is implemented in the next scoring step.
     */
    /**
 * Calculate the four cognitive area scores from the nine
 * normalized subtest scores.
 *
 * Expected keys:
 * SE, WA, AN, GE, RA, ZR, FA, WU, ME
 */
public function areaScores(array $subtestScores): array
{
    $required = [
        'SE', 'WA', 'AN', 'GE',
        'RA', 'ZR',
        'FA', 'WU',
        'ME',
    ];

    $normalized = [];

    foreach ($required as $code) {
        if (! array_key_exists($code, $subtestScores)) {
            throw new InvalidArgumentException(
                "Missing cognitive subtest score: {$code}."
            );
        }

        $score = (float) $this->canonicalNumber(
            $subtestScores[$code]
        );

        if ($score < 0.0 || $score > 100.0) {
            throw new InvalidArgumentException(
                "Cognitive subtest score {$code} must be between 0 and 100."
            );
        }

        $normalized[$code] = $score;
    }

    return [
        'verbal' => round(
            (
                $normalized['SE']
                + $normalized['WA']
                + $normalized['AN']
                + $normalized['GE']
            ) / 4,
            3,
        ),

        'numeric' => round(
            (
                $normalized['RA']
                + $normalized['ZR']
            ) / 2,
            3,
        ),

        'figural' => round(
            (
                $normalized['FA']
                + $normalized['WU']
            ) / 2,
            3,
        ),

        'memory' => round(
            $normalized['ME'],
            3,
        ),
    ];
}

/**
 * Cognitive Performance Index (IPK).
 *
 * Each of the four cognitive areas has equal weight.
 */
public function cognitivePerformanceIndex(
    array $areaScores,
): float {
    $required = [
        'verbal',
        'numeric',
        'figural',
        'memory',
    ];

    $values = [];

    foreach ($required as $area) {
        if (! array_key_exists($area, $areaScores)) {
            throw new InvalidArgumentException(
                "Missing cognitive area score: {$area}."
            );
        }

        $score = (float) $this->canonicalNumber(
            $areaScores[$area]
        );

        if ($score < 0.0 || $score > 100.0) {
            throw new InvalidArgumentException(
                "Cognitive area score {$area} must be between 0 and 100."
            );
        }

        $values[] = $score;
    }

    return round(
        array_sum($values) / count($values),
        3,
    );
}

/**
 * Main product performance category.
 */
public function performanceCategory(
    int|float|string $score,
): string {
    $score = $this->validatedPercentage($score);

    return match (true) {
        $score >= 85 => 'Sangat Unggul',
        $score >= 75 => 'Unggul',
        $score >= 65 => 'Baik',
        $score >= 55 => 'Cukup',
        $score >= 40 => 'Perlu Pengembangan',
        default => 'Perlu Perhatian',
    };
}

/**
 * Internal performance benchmark.
 */
public function performanceBenchmark(
    int|float|string $score,
): string {
    $score = $this->validatedPercentage($score);

    return match (true) {
        $score >= 85 => 'Jauh di Atas Rata-rata',
        $score >= 75 => 'Di Atas Rata-rata',
        $score >= 60 => 'Rata-rata',
        $score >= 45 => 'Di Bawah Rata-rata',
        default => 'Jauh di Bawah Rata-rata',
    };
}

/**
 * Return area names sorted from highest to lowest.
 */
public function rankedAreas(array $areaScores): array
{
    $scores = $this->validatedAreaScores($areaScores);

    arsort($scores, SORT_NUMERIC);

    return array_keys($scores);
}

/**
 * Two highest cognitive areas.
 */
public function strongestAreas(array $areaScores): array
{
    return array_slice(
        $this->rankedAreas($areaScores),
        0,
        2,
    );
}

/**
 * Lowest cognitive area(s).
 *
 * Multiple areas are returned when tied.
 */
public function developmentAreas(array $areaScores): array
{
    $scores = $this->validatedAreaScores($areaScores);

    $minimum = min($scores);

    return array_keys(
        array_filter(
            $scores,
            static fn (float $score): bool =>
                abs($score - $minimum) < 0.000001
        )
    );
}

public function profileSpread(array $areaScores): float
{
    $scores = $this->validatedAreaScores($areaScores);

    return round(
        max($scores) - min($scores),
        3,
    );
}

public function profileBalanceLabel(
    int|float|string $spread,
): string {
    $spread = (float) $this->canonicalNumber($spread);

    if ($spread < 0.0 || $spread > 100.0) {
        throw new InvalidArgumentException(
            'Cognitive profile spread must be between 0 and 100.'
        );
    }

    return match (true) {
        $spread < 10 => 'Relatif Seimbang',
        $spread < 20 => 'Terdapat Variasi Kemampuan',
        default => 'Perbedaan Kemampuan Cukup Menonjol',
    };
}

/**
 * Age group is informational only and does not modify the score.
 */
public function ageGroup(int $age): string
{
    if ($age < 18) {
        return 'Di bawah 18 tahun';
    }

    return match (true) {
        $age <= 24 => '18–24 tahun',
        $age <= 34 => '25–34 tahun',
        $age <= 44 => '35–44 tahun',
        $age <= 54 => '45–54 tahun',
        default => '55+ tahun',
    };
}

/**
 * Compatibility name for the existing database column.
 *
 * total_internal_score now stores the Cognitive Performance Index.
 */
public function totalInternalScore(
    array $subtestScores,
): ?float {
    if (count($subtestScores) !== self::FINAL_SUBTEST_COUNT) {
        return null;
    }

    $areas = $this->areaScores($subtestScores);

    return $this->cognitivePerformanceIndex($areas);
}

private function validatedPercentage(
    int|float|string $score,
): float {
    $value = (float) $this->canonicalNumber($score);

    if ($value < 0.0 || $value > 100.0) {
        throw new InvalidArgumentException(
            'Cognitive performance score must be between 0 and 100.'
        );
    }

    return $value;
}

private function validatedAreaScores(
    array $areaScores,
): array {
    $required = [
        'verbal',
        'numeric',
        'figural',
        'memory',
    ];

    $normalized = [];

    foreach ($required as $area) {
        if (! array_key_exists($area, $areaScores)) {
            throw new InvalidArgumentException(
                "Missing cognitive area score: {$area}."
            );
        }

        $normalized[$area] = $this->validatedPercentage(
            $areaScores[$area]
        );
    }

    return $normalized;
}

    /**
     * Normalize numeric values for deterministic comparison.
     */
    private function canonicalNumber(
        int|float|string $value,
    ): string {
        $number = trim((string) $value);

        if (
            ! preg_match(
                '/^[+-]?(?:\d+(?:\.\d*)?|\.\d+)$/',
                $number,
            )
        ) {
            throw new InvalidArgumentException(
                'IST numeric value must be a plain decimal number.'
            );
        }

        $negative = str_starts_with($number, '-');
        $unsigned = ltrim($number, '+-');

        [$integer, $fraction] = array_pad(
            explode('.', $unsigned, 2),
            2,
            '',
        );

        $integer = ltrim($integer, '0');
        $integer = $integer === '' ? '0' : $integer;

        $fraction = rtrim($fraction, '0');

        $canonical = $fraction === ''
            ? $integer
            : $integer.'.'.$fraction;

        if ($canonical === '0') {
            return '0';
        }

        return $negative
            ? '-'.$canonical
            : $canonical;
    }

    private function result(
        int|float $awardedScore,
        string $outcome,
    ): array {
        return [
            'awarded_score' => $awardedScore,
            'outcome' => $outcome,
        ];
    }
}
