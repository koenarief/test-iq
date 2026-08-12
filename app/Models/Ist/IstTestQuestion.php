<?php

namespace App\Models\Ist;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class IstTestQuestion extends Model
{
    /**
     * This model must never be sent directly as a participant payload.
     * Controllers must explicitly shape participant-safe question data.
     */
    protected $hidden = [
        'answer_key_snapshot',
    ];

    protected $fillable = [
        'ist_test_subtest_id',
        'source_question_id',
        'display_order',
        'answer_type',
        'question_snapshot',
        'options_snapshot',
        'answer_key_snapshot',
        'max_score',
        'difficulty',
    ];

    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
            'question_snapshot' => 'array',
            'options_snapshot' => 'array',
            'answer_key_snapshot' => 'array',
            'max_score' => 'decimal:4',
        ];
    }

    public function testSubtest(): BelongsTo
    {
        return $this->belongsTo(IstTestSubtest::class, 'ist_test_subtest_id');
    }

    public function sourceQuestion(): BelongsTo
    {
        return $this->belongsTo(IstQuestion::class, 'source_question_id');
    }

    public function answer(): HasOne
    {
        return $this->hasOne(IstAnswer::class);
    }
}
