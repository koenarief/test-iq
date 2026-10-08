<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Position extends Model
{
    protected $fillable = [
        'name',
        'department',
        'disc_weight',
        'ist_weight',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'disc_weight' => 'decimal:2',
            'ist_weight' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function competencyCategoryWeights(): HasMany
    {
        return $this->hasMany(PositionCompetencyCategoryWeight::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function candidateAssessments(): HasMany
    {
        return $this->hasMany(CandidateAssessment::class);
    }

    public function totalWeight(): float
    {
        return (float) $this->disc_weight
            + (float) $this->ist_weight
            + (float) $this->competencyCategoryWeights->sum('weight');
    }
}
