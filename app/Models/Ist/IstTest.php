<?php

namespace App\Models\Ist;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IstTest extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $hidden = [
        'access_token_hash',
    ];

    protected $fillable = [
        'public_id',
        'access_token_hash',
        'user_id',
        'participant_name',
        'age',
        'gender',
        'status',
        'current_subtest_sequence',
        'started_at',
        'finished_at',
        'total_internal_score',
    ];

    protected function casts(): array
    {
        return [
            'age' => 'integer',
            'current_subtest_sequence' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'total_internal_score' => 'decimal:3',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subtests(): HasMany
    {
        return $this->hasMany(IstTestSubtest::class);
    }
}
