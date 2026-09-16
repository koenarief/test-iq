<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompetencyCategory extends Model
{
    protected $fillable = [
        'department',
        'name',
        'order',
    ];

    public function questions(): HasMany
    {
        return $this->hasMany(CompetencyQuestion::class);
    }
}
