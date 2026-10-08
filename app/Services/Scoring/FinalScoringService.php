<?php

namespace App\Services\Scoring;

use App\Models\CandidateAssessment;

/**
 * Final Score = (Assessment x 50%) + (Job Fit x 20%) + (Interview x 30%),
 * only meaningful once the candidate passed the Assessment Gate and both
 * Job Fit and Interview scores are available.
 */
class FinalScoringService
{
    public const ASSESSMENT_WEIGHT = 0.50;

    public const JOB_FIT_WEIGHT = 0.20;

    public const INTERVIEW_WEIGHT = 0.30;

    public const RECOMMENDED_MIN = 85.0;

    public const CONSIDER_MIN = 75.0;

    /**
     * @return array{final_score: float, decision: string}
     */
    public function score(CandidateAssessment $assessment): array
    {
        $finalScore = round(
            (float) $assessment->assessment_score * self::ASSESSMENT_WEIGHT
            + (float) $assessment->job_fit_score * self::JOB_FIT_WEIGHT
            + (float) $assessment->interview_score * self::INTERVIEW_WEIGHT,
            2,
        );

        $decision = match (true) {
            $finalScore >= self::RECOMMENDED_MIN => CandidateAssessment::DECISION_RECOMMENDED,
            $finalScore >= self::CONSIDER_MIN => CandidateAssessment::DECISION_CONSIDER,
            default => CandidateAssessment::DECISION_NOT_RECOMMENDED,
        };

        return [
            'final_score' => $finalScore,
            'decision' => $decision,
        ];
    }
}
