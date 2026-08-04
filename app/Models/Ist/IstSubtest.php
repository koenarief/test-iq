<?php

namespace App\Models\Ist;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IstSubtest extends Model
{
    protected $fillable = [
        'code',
        'name',
        'sequence',
        'question_count',
        'default_answer_type',
        'instruction_content',
        'memorization_content',
        'duration_seconds',
        'memorization_seconds',
        'answering_seconds',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'question_count' => 'integer',
            'duration_seconds' => 'integer',
            'memorization_seconds' => 'integer',
            'answering_seconds' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function questions(): HasMany
    {
        return $this->hasMany(IstQuestion::class);
    }

    public function testSubtests(): HasMany
    {
        return $this->hasMany(IstTestSubtest::class);
    }
}
