<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'merchant_id', 'position_id', 'instansi_id', 'jabatan_id', 'no_whatsapp', 'status'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_MERCHANT = 'merchant';

    public const ROLE_HRD = 'hrd';

    public const ROLE_KARYAWAN = 'karyawan';

    public const ROLE_CANDIDATE = 'candidate';

    public const STATUS_PENDING = 'pending';

    public const STATUS_AKTIF = 'aktif';

    public const STATUS_DITOLAK = 'ditolak';

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'role' => self::ROLE_ADMIN,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * instansi_id/jabatan_id are mandatory for every user. Admin/merchant/hrd
     * accounts (created via the admin panel, factories, or seeders) fall
     * back to the default instansi/jabatan seeded by the absensi migration
     * when left unset. karyawan is deliberately excluded: self-registration
     * must always supply its own validated instansi_id/jabatan_id, so a bug
     * that forgets to set them fails loudly on the NOT NULL constraint
     * instead of silently attaching the karyawan to "Kantor Pusat".
     */
    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            if ($user->role === self::ROLE_KARYAWAN) {
                return;
            }

            $user->instansi_id ??= Instansi::query()->value('id');
            $user->jabatan_id ??= Jabatan::query()->value('id');
        });
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function candidateAssessment(): HasOne
    {
        return $this->hasOne(CandidateAssessment::class);
    }

    public function instansi(): BelongsTo
    {
        return $this->belongsTo(Instansi::class);
    }

    public function jabatan(): BelongsTo
    {
        return $this->belongsTo(Jabatan::class);
    }

    public function absensi(): HasMany
    {
        return $this->hasMany(Absensi::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isMerchant(): bool
    {
        return $this->role === self::ROLE_MERCHANT;
    }

    public function isHrd(): bool
    {
        return $this->role === self::ROLE_HRD;
    }

    public function isKaryawan(): bool
    {
        return $this->role === self::ROLE_KARYAWAN;
    }

    public function isCandidate(): bool
    {
        return $this->role === self::ROLE_CANDIDATE;
    }

    /**
     * HRD absensi features (approve registration, view attendance recap)
     * are also open to admin accounts, without changing their stored role,
     * so the existing Tes-IQ admin dashboard and permissions stay untouched.
     */
    public function canManageAbsensi(): bool
    {
        return $this->isHrd() || $this->isAdmin();
    }

    public function isAktif(): bool
    {
        return $this->status === self::STATUS_AKTIF;
    }
}
