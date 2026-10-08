<?php

namespace App\Services\Scoring;

use App\Models\CandidateAssessment;
use App\Models\CompetencyTest;
use App\Models\DiscTest;
use App\Models\Ist\IstTest;
use App\Models\User;

/**
 * Computes the score.md "Assessment Score" = (DISC x 20%) + (IST x 25%) +
 * (Competency x 55%) and its Minimum Score Gate, for a candidate whose
 * three test attempts (DISC/IST/Competency) may still be incomplete.
 *
 * DISC and IST have no native 0-100 score in this codebase, so this class
 * defines the house formulas:
 * - DISC score = the candidate's dominant-dimension graph score
 *   (disc_tests.graph_d/i/s/c are already 0-100, see DiscTestController).
 * - IST score  = average of ist_test_subtests.percentage across the 9
 *   completed subtests (already a 0-100 per-subtest correct/max ratio).
 * Competency score reuses the existing overallPercentage formula from
 * CompetencyTestController::result() (total_score/5*100).
 */
class AssessmentScoringService
{
    public const DISC_WEIGHT = 0.20;

    public const IST_WEIGHT = 0.25;

    public const COMPETENCY_WEIGHT = 0.55;

    public const DISC_GATE_MIN = 60.0;

    public const IST_GATE_MIN = 60.0;

    public const COMPETENCY_GATE_MIN = 65.0;

    public const ASSESSMENT_GATE_MIN = 70.0;

    public function __construct(
        private readonly JobFitScoringService $jobFitScoringService,
        private readonly StrengthDevelopmentService $strengthDevelopmentService,
    ) {}

    public static function scoreDisc(DiscTest $test): float
    {
        return (float) max(
            (float) $test->graph_d,
            (float) $test->graph_i,
            (float) $test->graph_s,
            (float) $test->graph_c,
        );
    }

    public static function scoreIst(IstTest $test): float
    {
        $percentages = $test->subtests()->pluck('percentage');

        if ($percentages->isEmpty()) {
            return 0.0;
        }

        return round((float) $percentages->avg(), 2);
    }

    public static function scoreCompetency(CompetencyTest $test): float
    {
        return round(((float) $test->total_score / 5) * 100, 2);
    }

    public function recomputeForCandidate(User $candidate): CandidateAssessment
    {
        $discTest = DiscTest::query()
            ->where('user_id', $candidate->id)
            ->where('status', 'completed')
            ->latest('id')
            ->first();

        $competencyTest = CompetencyTest::query()
            ->where('user_id', $candidate->id)
            ->where('status', 'completed')
            ->latest('id')
            ->first();

        $istTest = IstTest::query()
            ->where('user_id', $candidate->id)
            ->where('status', IstTest::STATUS_COMPLETED)
            ->latest('id')
            ->first();

        $assessment = CandidateAssessment::query()->firstOrNew(['user_id' => $candidate->id]);
        $assessment->position_id ??= $candidate->position_id;
        $assessment->disc_test_id = $discTest?->id;
        $assessment->competency_test_id = $competencyTest?->id;
        $assessment->ist_test_id = $istTest?->id;

        if (! $discTest || ! $competencyTest || ! $istTest) {
            $assessment->disc_score = $discTest ? self::scoreDisc($discTest) : null;
            $assessment->competency_score = $competencyTest ? self::scoreCompetency($competencyTest) : null;
            $assessment->ist_score = $istTest ? self::scoreIst($istTest) : null;
            $assessment->save();

            return $assessment;
        }

        $discScore = self::scoreDisc($discTest);
        $istScore = self::scoreIst($istTest);
        $competencyScore = self::scoreCompetency($competencyTest);

        $assessmentScore = round(
            $discScore * self::DISC_WEIGHT
            + $istScore * self::IST_WEIGHT
            + $competencyScore * self::COMPETENCY_WEIGHT,
            2,
        );

        $gateDisc = $discScore >= self::DISC_GATE_MIN;
        $gateIst = $istScore >= self::IST_GATE_MIN;
        $gateCompetency = $competencyScore >= self::COMPETENCY_GATE_MIN;
        $gateAssessment = $assessmentScore >= self::ASSESSMENT_GATE_MIN;
        $gatePassed = $gateDisc && $gateIst && $gateCompetency && $gateAssessment;

        $assessment->fill([
            'disc_score' => $discScore,
            'ist_score' => $istScore,
            'competency_score' => $competencyScore,
            'assessment_score' => $assessmentScore,
            'gate_disc_passed' => $gateDisc,
            'gate_ist_passed' => $gateIst,
            'gate_competency_passed' => $gateCompetency,
            'gate_assessment_passed' => $gateAssessment,
            'gate_passed' => $gatePassed,
            'assessment_scored_at' => now(),
        ]);

        if ($gatePassed) {
            $assessment->strength_development_breakdown = $this->strengthDevelopmentService->build(
                $competencyTest,
                $discTest,
                $istTest,
            );

            if ($assessment->position_id) {
                $jobFit = $this->jobFitScoringService->score($assessment, $competencyTest, $discTest, $istTest);
                $assessment->job_fit_score = $jobFit['score'];
                $assessment->job_fit_breakdown = $jobFit['breakdown'];
                $assessment->job_fit_scored_at = now();
            }
        }

        $assessment->save();

        return $assessment;
    }
}
