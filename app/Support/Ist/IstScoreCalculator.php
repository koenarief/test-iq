<?php

namespace App\Support\Ist;

use App\Models\Ist\IstAnswer;
use InvalidArgumentException;

final class IstScoreCalculator
{
    private const FINAL_SUBTEST_COUNT = 9;

    /**
     * Binary rule: score 1 is correct; score 0 is wrong; null is blank.
     */
    public function scoreBinary(?bool $isCorrect): array
    {
        if ($isCorrect === null) {
            return $this->result(0, IstAnswer::OUTCOME_BLANK);
        }

        return $isCorrect
            ? $this->result(1, IstAnswer::OUTCOME_CORRECT)
            : $this->result(0, IstAnswer::OUTCOME_WRONG);
    }

    /**
     * GE rule: 4 is correct, 1-3 is partial, 0 is wrong, null is blank.
     */
    public function scoreWeighted(int|float|string|null $scoreValue): array
    {
        if ($scoreValue === null || $scoreValue === '') {
            return $this->result(0, IstAnswer::OUTCOME_BLANK);
        }

        $canonical = $this->canonicalNumber($scoreValue);

        if (! in_array($canonical, ['0', '1', '2', '3', '4'], true)) {
            throw new InvalidArgumentException('Weighted IST score must be an integer from 0 to 4.');
        }

        $score = (int) $canonical;

        if ($score === 4) {
            return $this->result(4, IstAnswer::OUTCOME_CORRECT);
        }

        if ($score >= 1) {
            return $this->result($score, IstAnswer::OUTCOME_PARTIAL);
        }

        return $this->result(0, IstAnswer::OUTCOME_WRONG);
    }

    public function scoreNumeric(
        int|float|string|null $numericAnswer,
        int|float|string $answerKey
    ): array {
        if ($numericAnswer === null || trim((string) $numericAnswer) === '') {
            return $this->result(0, IstAnswer::OUTCOME_BLANK);
        }

        $isCorrect = $this->canonicalNumber($numericAnswer)
            === $this->canonicalNumber($answerKey);

        return $this->scoreBinary($isCorrect);
    }

    public function percentage(int|float|string $awardedScore, int|float|string $maxScore): float
    {
        $awarded = (float) $this->canonicalNumber($awardedScore);
        $maximum = (float) $this->canonicalNumber($maxScore);

        if ($maximum <= 0.0) {
            return 0.0;
        }

        return round(($awarded / $maximum) * 100, 3);
    }

    /**
     * Returns null until exactly nine finalized subtest percentages exist.
     */
    public function totalInternalScore(array $finalPercentages): ?float
    {
        if (count($finalPercentages) !== self::FINAL_SUBTEST_COUNT) {
            return null;
        }

        $percentages = array_map(function ($percentage): float {
            $value = (float) $this->canonicalNumber($percentage);

            if ($value < 0.0 || $value > 100.0) {
                throw new InvalidArgumentException('IST subtest percentage must be between 0 and 100.');
            }

            return $value;
        }, array_values($finalPercentages));

        return round(array_sum($percentages) / self::FINAL_SUBTEST_COUNT, 3);
    }

    private function canonicalNumber(int|float|string $value): string
    {
        $number = trim((string) $value);

        if (! preg_match('/^[+-]?(?:\d+(?:\.\d*)?|\.\d+)$/', $number)) {
            throw new InvalidArgumentException('IST numeric value must be a plain decimal number.');
        }

        $negative = str_starts_with($number, '-');
        $unsigned = ltrim($number, '+-');
        [$integer, $fraction] = array_pad(explode('.', $unsigned, 2), 2, '');

        $integer = ltrim($integer, '0');
        $integer = $integer === '' ? '0' : $integer;
        $fraction = rtrim($fraction, '0');

        $canonical = $fraction === '' ? $integer : $integer.'.'.$fraction;

        if ($canonical === '0') {
            return '0';
        }

        return $negative ? '-'.$canonical : $canonical;
    }

    private function result(int|float $awardedScore, string $outcome): array
    {
        return [
            'awarded_score' => $awardedScore,
            'outcome' => $outcome,
        ];
    }
}
