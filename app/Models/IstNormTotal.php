<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IstNormTotal extends Model
{
    use HasFactory;

    protected $table = 'ist_norm_totals';

    protected $fillable = [
        'min_age',
        'max_age',
        'total_sw',
        'iq_score',
        'iq_category',
    ];

    protected $casts = [
        'min_age' => 'integer',
        'max_age' => 'integer',
        'total_sw' => 'integer',
        'iq_score' => 'integer',
    ];

    /**
     * Scope untuk pencarian norma Gesamt (IQ & Kategori) berdasarkan usia dan Total SW
     */
    public function scopeLookup($query, int $age, int $totalSw)
    {
        return $query->where('min_age', '<=', $age)
                     ->where('max_age', '>=', $age)
                     ->where('total_sw', $totalSw);
    }
}