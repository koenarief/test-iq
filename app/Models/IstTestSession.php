<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IstTestSession extends Model
{
    use HasFactory;

    protected $table = 'ist_test_sessions';

    protected $guarded = ['id'];

    protected $casts = [
        'birth_date' => 'date',
        'test_date' => 'date',
        'age' => 'integer',
        'iq_score' => 'integer',
    ];

    public function responses(): HasMany
    {
        return $this->hasMany(IstUserResponse::class, 'test_session_id');
    }
}