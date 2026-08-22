<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IstAnswerKey extends Model
{
    use HasFactory;

    protected $table = 'ist_answer_keys';

    protected $fillable = [
        'subtest',
        'question_number',
        'correct_answer',
        'score_weight',
    ];

    protected $casts = [
        'question_number' => 'integer',
        'score_weight' => 'integer',
    ];
}