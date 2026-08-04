<?php

namespace App\Models\Ist;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IstTestSubtest extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_INSTRUCTION = 'instruction';

    public const STATUS_MEMORIZING = 'memorizing';

    public const STATUS_ANSWERING = 'answering';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_TIMED_OUT = 'timed_out';

    public const FINALIZED_SUBMITTED = 'submitted';

    public const FINALIZED_TIMEOUT = 'timeout';

    protected $fillable = [
        'ist_test_id',
        'ist_subtest_id',
        'sequence',
        'status',
        'question_count',
        'instruction_viewed_at',
        'started_at',
        'memorization_started_at',
        'memorization_ends_at',
        'answering_started_at',
        'answering_ends_at',
        'locked_at',
        'finalized_reason',
        'awarded_score',
        'max_score',
        'correct_count',
        'partial_count',
        'wrong_count',
        'blank_count',
        'percentage',
        'last_autosaved_at',
    ];

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'question_count' => 'integer',
            'instruction_viewed_at' => 'datetime',
            'started_at' => 'datetime',
            'memorization_started_at' => 'datetime',
            'memorization_ends_at' => 'datetime',
            'answering_started_at' => 'datetime',
            'answering_ends_at' => 'datetime',
            'locked_at' => 'datetime',
            'awarded_score' => 'decimal:4',
            'max_score' => 'decimal:4',
            'correct_count' => 'integer',
            'partial_count' => 'integer',
            'wrong_count' => 'integer',
            'blank_count' => 'integer',
            'percentage' => 'decimal:3',
            'last_autosaved_at' => 'datetime',
        ];
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(IstTest::class, 'ist_test_id');
    }

    public function subtest(): BelongsTo
    {
        return $this->belongsTo(IstSubtest::class, 'ist_subtest_id');
    }

    public function testQuestions(): HasMany
    {
        return $this->hasMany(IstTestQuestion::class);
    }
}
