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
        'slug',
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
            $merchant->slug ??= static::uniqueSlugFor($merchant->name);
        });
    }

    /**
     * Slugs are generated once at creation and then left stable, so a link
     * already shared with candidates keeps working even if the merchant's
     * display name is edited later.
     */
    private static function uniqueSlugFor(string $name): string
    {
        $base = Str::slug($name) ?: 'merchant';
        $slug = $base;
        $suffix = 2;

        while (static::query()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
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
