<?php

namespace App\Models;

use App\Models\Ist\IstTest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateAssessment extends Model
{
    public const DECISION_RECOMMENDED = 'recommended';

    public const DECISION_CONSIDER = 'consider';

    public const DECISION_NOT_RECOMMENDED = 'not_recommended';

    protected $fillable = [
        'user_id',
        'position_id',
        'disc_test_id',
        'competency_test_id',
        'ist_test_id',

        'disc_score',
        'ist_score',
        'competency_score',
        'assessment_score',
        'gate_disc_passed',
        'gate_ist_passed',
        'gate_assessment_passed',
        'gate_passed',
        'assessment_scored_at',

        'job_fit_score',
        'job_fit_breakdown',
        'job_fit_scored_at',

        'strength_development_breakdown',

        'interview_hrd_attitude_ethics',
        'interview_hrd_communication',
        'interview_hrd_motivation_goals',
        'interview_hrd_discipline_responsibility',
        'interview_hrd_commitment_readiness',
        'interview_hrd_adaptability_teamwork',
        'interview_hrd_score',
        'interview_hrd_notes',
        'interview_hrd_by',
        'interview_hrd_scored_at',

        'interview_user_task_understanding',
        'interview_user_work_competence',
        'interview_user_problem_analysis',
        'interview_user_work_quality',
        'interview_user_initiative_independence',
        'interview_user_communication_teamwork',
        'interview_user_score',
        'interview_user_notes',
        'interview_user_by',
        'interview_user_scored_at',

        'interview_score',
        'interview_scored_at',

        'final_score',
        'decision',
        'final_scored_at',
    ];

    protected function casts(): array
    {
        return [
            'disc_score' => 'decimal:2',
            'ist_score' => 'decimal:2',
            'competency_score' => 'decimal:2',
            'assessment_score' => 'decimal:2',
            'gate_disc_passed' => 'boolean',
            'gate_ist_passed' => 'boolean',
            'gate_assessment_passed' => 'boolean',
            'gate_passed' => 'boolean',
            'assessment_scored_at' => 'datetime',

            'job_fit_score' => 'decimal:2',
            'job_fit_breakdown' => 'array',
            'job_fit_scored_at' => 'datetime',

            'strength_development_breakdown' => 'array',

            'interview_hrd_attitude_ethics' => 'decimal:2',
            'interview_hrd_communication' => 'decimal:2',
            'interview_hrd_motivation_goals' => 'decimal:2',
            'interview_hrd_discipline_responsibility' => 'decimal:2',
            'interview_hrd_commitment_readiness' => 'decimal:2',
            'interview_hrd_adaptability_teamwork' => 'decimal:2',
            'interview_hrd_score' => 'decimal:2',
            'interview_hrd_scored_at' => 'datetime',

            'interview_user_task_understanding' => 'decimal:2',
            'interview_user_work_competence' => 'decimal:2',
            'interview_user_problem_analysis' => 'decimal:2',
            'interview_user_work_quality' => 'decimal:2',
            'interview_user_initiative_independence' => 'decimal:2',
            'interview_user_communication_teamwork' => 'decimal:2',
            'interview_user_score' => 'decimal:2',
            'interview_user_scored_at' => 'datetime',

            'interview_score' => 'decimal:2',
            'interview_scored_at' => 'datetime',

            'final_score' => 'decimal:2',
            'final_scored_at' => 'datetime',
        ];
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function discTest(): BelongsTo
    {
        return $this->belongsTo(DiscTest::class);
    }

    public function competencyTest(): BelongsTo
    {
        return $this->belongsTo(CompetencyTest::class);
    }

    public function istTest(): BelongsTo
    {
        return $this->belongsTo(IstTest::class, 'ist_test_id');
    }

    public function hrdInterviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'interview_hrd_by');
    }

    public function userInterviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'interview_user_by');
    }
}
