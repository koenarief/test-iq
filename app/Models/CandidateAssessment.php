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
        'gate_competency_passed',
        'gate_assessment_passed',
        'gate_passed',
        'assessment_scored_at',

        'job_fit_score',
        'job_fit_breakdown',
        'job_fit_scored_at',

        'strength_development_breakdown',

        'interview_communication',
        'interview_motivation',
        'interview_problem_solving',
        'interview_job_knowledge',
        'interview_attitude',
        'interview_work_alignment',
        'interview_score',
        'interview_notes',
        'interviewed_by',
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
            'gate_competency_passed' => 'boolean',
            'gate_assessment_passed' => 'boolean',
            'gate_passed' => 'boolean',
            'assessment_scored_at' => 'datetime',

            'job_fit_score' => 'decimal:2',
            'job_fit_breakdown' => 'array',
            'job_fit_scored_at' => 'datetime',

            'strength_development_breakdown' => 'array',

            'interview_communication' => 'decimal:2',
            'interview_motivation' => 'decimal:2',
            'interview_problem_solving' => 'decimal:2',
            'interview_job_knowledge' => 'decimal:2',
            'interview_attitude' => 'decimal:2',
            'interview_work_alignment' => 'decimal:2',
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

    public function interviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'interviewed_by');
    }
}
