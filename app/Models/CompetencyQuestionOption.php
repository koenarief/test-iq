<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompetencyQuestionOption extends Model
{
    protected $fillable = [
        'competency_question_id',
        'label',
        'option_text',
        'points',
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(CompetencyQuestion::class, 'competency_question_id');
    }
}
