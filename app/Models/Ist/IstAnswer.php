<?php

namespace App\Models\Ist;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IstAnswer extends Model
{
    public const OUTCOME_CORRECT = 'correct';

    public const OUTCOME_PARTIAL = 'partial';

    public const OUTCOME_WRONG = 'wrong';

    public const OUTCOME_BLANK = 'blank';

    protected $fillable = [
        'ist_test_question_id',
        'selected_option_key',
        'numeric_answer',
        'awarded_score',
        'outcome',
        'client_revision',
        'saved_at',
    ];

    protected function casts(): array
    {
        return [
            'numeric_answer' => 'decimal:6',
            'awarded_score' => 'decimal:4',
            'client_revision' => 'integer',
            'saved_at' => 'datetime',
        ];
    }

    public function testQuestion(): BelongsTo
    {
        return $this->belongsTo(IstTestQuestion::class, 'ist_test_question_id');
    }
}
