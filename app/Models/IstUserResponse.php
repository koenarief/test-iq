<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IstUserResponse extends Model
{
    use HasFactory;

    protected $table = 'ist_user_responses';

    protected $fillable = [
        'test_session_id',
        'subtest',
        'question_number',
        'user_answer',
        'earned_score',
    ];

    protected $casts = [
        'question_number' => 'integer',
        'earned_score' => 'integer',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(IstTestSession::class, 'test_session_id');
    }
}