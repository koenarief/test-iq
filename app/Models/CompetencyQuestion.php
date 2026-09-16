<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompetencyQuestion extends Model
{
    protected $fillable = [
        'competency_category_id',
        'question_number',
        'question_text',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(CompetencyCategory::class, 'competency_category_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(CompetencyQuestionOption::class);
    }
}
