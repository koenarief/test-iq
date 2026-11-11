<?php

namespace App\Models;

use App\Models\DiscTest;
use App\Models\Ist\IstTest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Merchant extends Model
{
    protected $fillable = [
        'public_id',
        'name',
        'slug',
        'instansi_id',
        'logo_path',
        'is_active',
        'is_trial',
        'max_participants',
    ];

    protected $appends = ['logo_url'];

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }

    public function instansi(): BelongsTo
    {
        return $this->belongsTo(Instansi::class);
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_trial' => 'boolean',
            'max_participants' => 'integer',
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

    /**
     * Peserta = kandidat yang memulai rangkaian tes (satu DiscTest per peserta).
     */
    public function participantCount(): int
    {
        return $this->discTests()->count();
    }

    public function trialLimitReached(): bool
    {
        return $this->is_trial
            && $this->max_participants !== null
            && $this->participantCount() >= $this->max_participants;
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

    public function competencyTests(): HasMany
    {
        return $this->hasMany(CompetencyTest::class);
    }
}
