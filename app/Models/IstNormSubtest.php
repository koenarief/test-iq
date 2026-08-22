<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IstNormSubtest extends Model
{
    use HasFactory;

    protected $table = 'ist_norm_subtests';

    protected $fillable = [
        'min_age',
        'max_age',
        'subtest',
        'raw_score',
        'standard_score',
    ];

    protected $casts = [
        'min_age' => 'integer',
        'max_age' => 'integer',
        'raw_score' => 'integer',
        'standard_score' => 'integer',
    ];

    /**
     * Scope untuk pencarian norma subtes berdasarkan usia, jenis subtes, dan skor mentah (RW)
     */
    public function scopeLookup($query, int $age, string $subtest, int $rawScore)
    {
        return $query->where('subtest', strtoupper($subtest))
                     ->where('min_age', '<=', $age)
                     ->where('max_age', '>=', $age)
                     ->where('raw_score', $rawScore);
    }
}