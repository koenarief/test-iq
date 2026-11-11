<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Position extends Model
{
    protected $fillable = [
        'name',
        'department',
        'instansi_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function instansi(): BelongsTo
    {
        return $this->belongsTo(Instansi::class);
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
        return (float) $this->competencyCategoryWeights->sum('weight');
    }
}
