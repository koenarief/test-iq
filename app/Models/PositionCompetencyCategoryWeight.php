<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PositionCompetencyCategoryWeight extends Model
{
    protected $fillable = [
        'position_id',
        'competency_category_id',
        'weight',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:2',
        ];
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CompetencyCategory::class, 'competency_category_id');
    }
}
