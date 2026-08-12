<?php

namespace App\Models\Ist;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class IstQuestion extends Model
{
    use SoftDeletes;

    public const KIND_SCORED = 'scored';

    public const KIND_EXAMPLE = 'example';

    protected $fillable = [
        'ist_subtest_id',
        'question_number',
        'display_order',
        'kind',
        'answer_type',
        'prompt',
        'image_disk',
        'image_path',
        'image_alt',
        'example_explanation',
        'numeric_answer_key',
        'max_score',
        'difficulty',
        'version',
        'is_active',
        'created_by',
        'updated_by',

    ];

    protected function casts(): array
    {
        return [
            'question_number' => 'integer',
            'display_order' => 'integer',
            'numeric_answer_key' => 'decimal:6',
            'max_score' => 'decimal:4',
            'version' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function subtest(): BelongsTo
    {
        return $this->belongsTo(IstSubtest::class, 'ist_subtest_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(IstQuestionOption::class);
    }

    public function testQuestions(): HasMany
    {
        return $this->hasMany(IstTestQuestion::class, 'source_question_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
