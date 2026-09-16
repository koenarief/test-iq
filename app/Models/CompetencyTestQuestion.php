<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompetencyTestQuestion extends Model
{
    protected $fillable = [
        'competency_test_id',
        'competency_question_id',
        'competency_category_id',
        'order',
    ];

    public function test(): BelongsTo
    {
        return $this->belongsTo(CompetencyTest::class, 'competency_test_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(CompetencyQuestion::class, 'competency_question_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CompetencyCategory::class, 'competency_category_id');
    }
}
