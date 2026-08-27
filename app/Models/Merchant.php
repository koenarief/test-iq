<?php

namespace App\Models;

use App\Models\DiscTest;
use App\Models\Ist\IstTest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Merchant extends Model
{
    protected $fillable = [
        'public_id',
        'name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Merchant $merchant): void {
            $merchant->public_id ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function istTests(): HasMany
    {
        return $this->hasMany(IstTest::class);
    }

    public function discTests(): HasMany
    {
        return $this->hasMany(DiscTest::class);
    }
}
