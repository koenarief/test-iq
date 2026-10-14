<?php

namespace App\Services\Scoring;

use App\Exceptions\Ist\IstNormLookupException;
use App\Models\CandidateAssessment;
use App\Models\CompetencyTest;
use App\Models\DiscTest;
use App\Models\Ist\IstTest;
use App\Models\Position;
use App\Models\User;
use App\Services\Ist\IstScoringService;

/**
 * konsep-hrd.pdf scoring engine for Assessment, Job Fit, Strength &
 * Development, and (once Interview is available) Final Score.
 *
 * - ASSESSMENT = DISC x50% + IST x50%. Competency is NOT part of Assessment
 *   (it only feeds Job Fit) — only needs a completed DISC + IST test.
 * - DISC score = the candidate's dominant-dimension graph score
 *   (disc_tests.graph_d/i/s/c are already 0-100, see DiscTestController).
 * - IST score = the age-normed IQ score shown on the Hasil IST page (see
 *   IstResultService/IstScoringService::calculateFromRawScores()).
 * - competency_score is informational only (raw Competency test result),
 *   decoupled from both Assessment and Job Fit.
 * - DISC/IST minimums and the Assessment indicator threshold are display
 *   indicators only ("Memenuhi Kriteria" / "Di Bawah Kriteria") — they never
 *   block Job Fit, Strength & Development, or the Interview flow from being
 *   computed/available.
 * - Job Fit (see JobFitScoringService) is only computed once per candidate:
 *   once `job_fit_scored_at` is set, later calls skip it unless $force is
 *   passed, so a later change to a position's subtest weights doesn't
 *   retroactively change an already-scored candidate's Job Fit.
 */
class AssessmentScoringService
{
    public const DISC_WEIGHT = 0.50;

    public const IST_WEIGHT = 0.50;

    public const DISC_INDICATOR_MIN = 60.0;

    public const IST_INDICATOR_MIN = 60.0;

    public const ASSESSMENT_INDICATOR_MIN = 75.0;

    public function __construct(
        private readonly JobFitScoringService $jobFitScoringService,
        private readonly StrengthDevelopmentService $strengthDevelopmentService,
        private readonly IstScoringService $istScoringService,
        private readonly FinalScoringService $finalScoringService,
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

    public function scoreIst(IstTest $test): float
    {
        $rawScores = [];

        foreach ($test->subtests()->with('subtest')->get() as $runtime) {
            if (! $runtime->subtest) {
                continue;
            }

            $code = strtoupper(trim((string) $runtime->subtest->code));
            $rawScores[$code] = (int) round((float) $runtime->awarded_score);
        }

        if (count($rawScores) !== 9) {
            return 0.0;
        }

        try {
            $scoring = $this->istScoringService->calculateFromRawScores($rawScores, (int) $test->age);
        } catch (IstNormLookupException) {
            return 0.0;
        }

        return (float) $scoring['iq_score'];
    }

    public static function scoreCompetency(CompetencyTest $test): float
    {
        return round(((float) $test->total_score / 5) * 100, 2);
    }

    public function recomputeForCandidate(User $candidate, bool $force = false): CandidateAssessment
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

        $assessment->competency_score = $competencyTest ? self::scoreCompetency($competencyTest) : null;

        // ASSESSMENT = DISC x50% + IST x50% — only needs DISC + IST.
        if ($discTest && $istTest) {
            $discScore = self::scoreDisc($discTest);
            $istScore = $this->scoreIst($istTest);

            $assessmentScore = round(
                $discScore * self::DISC_WEIGHT
                + $istScore * self::IST_WEIGHT,
                2,
            );

            $gateDisc = $discScore >= self::DISC_INDICATOR_MIN;
            $gateIst = $istScore >= self::IST_INDICATOR_MIN;
            $gateAssessment = $assessmentScore >= self::ASSESSMENT_INDICATOR_MIN;

            $assessment->fill([
                'disc_score' => $discScore,
                'ist_score' => $istScore,
                'assessment_score' => $assessmentScore,
                'gate_disc_passed' => $gateDisc,
                'gate_ist_passed' => $gateIst,
                'gate_assessment_passed' => $gateAssessment,
                // Informational aggregate only — never gates anything downstream.
                'gate_passed' => $gateDisc && $gateIst && $gateAssessment,
                'assessment_scored_at' => now(),
            ]);
        } else {
            $assessment->disc_score = $discTest ? self::scoreDisc($discTest) : null;
            $assessment->ist_score = $istTest ? $this->scoreIst($istTest) : null;
        }

        // JOB FIT = Σ(skor subtes Competency x bobot subtes per posisi).
        // Scored once; a snapshot of the weights used is kept in
        // job_fit_breakdown, so later weight edits don't retroactively
        // change an already-scored candidate unless $force is passed.
        $position = $assessment->position_id
            ? Position::with('competencyCategoryWeights')->find($assessment->position_id)
            : null;

        if ($competencyTest && $position && ($force || $assessment->job_fit_scored_at === null)) {
            $jobFit = $this->jobFitScoringService->score($competencyTest, $position);
            $assessment->job_fit_score = $jobFit['score'];
            $assessment->job_fit_breakdown = $jobFit['breakdown'];
            $assessment->job_fit_scored_at = now();
        }

        // Strength & Development breakdown needs all three raw test results;
        // not admin-weight-dependent, so it's safe to always refresh.
        if ($discTest && $competencyTest && $istTest) {
            $assessment->strength_development_breakdown = $this->strengthDevelopmentService->build(
                $competencyTest,
                $discTest,
                $istTest,
            );
        }

        $assessment->save();

        $this->recomputeFinalScore($assessment);

        return $assessment;
    }

    /**
     * FINAL SCORE = Assessment x35% + Job Fit x35% + Interview x30%, only
     * once all three inputs are available. Called after Assessment/Job Fit
     * recompute here, and again after each Interview stage is saved.
     */
    public function recomputeFinalScore(CandidateAssessment $assessment): CandidateAssessment
    {
        if ($assessment->assessment_score === null
            || $assessment->job_fit_score === null
            || $assessment->interview_score === null
        ) {
            return $assessment;
        }

        $final = $this->finalScoringService->score($assessment);
        $assessment->final_score = $final['final_score'];
        $assessment->decision = $final['decision'];
        $assessment->final_scored_at = now();
        $assessment->save();

        return $assessment;
    }
}
