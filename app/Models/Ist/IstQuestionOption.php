<?php

namespace App\Models\Ist;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class IstQuestionOption extends Model
{
    use SoftDeletes;

    /**
     * Binary options use score 1 for correct and 0 for wrong.
     * GE uses 3 for correct, 1-2 for partial, and 0 for wrong.
     */
    protected $fillable = [
        'ist_question_id',
        'option_key',
        'option_text',
        'image_disk',
        'image_path',
        'image_alt',
        'display_order',
        'is_correct',
        'score_value',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
            'is_correct' => 'boolean',
            'score_value' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(IstQuestion::class, 'ist_question_id');
    }
}
