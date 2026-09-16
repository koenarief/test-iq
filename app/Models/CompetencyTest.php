<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompetencyTest extends Model
{
    protected $fillable = [
        'user_id',
        'merchant_id',
        'participant_name',
        'age',
        'gender',
        'department',
        'status',
        'started_at',
        'finished_at',
        'total_score',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'total_score' => 'decimal:2',
        ];
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function testQuestions(): HasMany
    {
        return $this->hasMany(CompetencyTestQuestion::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(CompetencyAnswer::class);
    }
}
