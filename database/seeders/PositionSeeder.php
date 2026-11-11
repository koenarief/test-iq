<?php

namespace Database\Seeders;

use App\Models\CompetencyCategory;
use App\Models\Instansi;
use App\Models\Position;
use Illuminate\Database\Seeder;

/**
 * Seeds the exact Sales Executive example from konsep-hrd.pdf so Job Fit
 * Score can be exercised end-to-end: Communication 25%, Sales Orientation
 * 25%, Problem Solving 25%, Negotiation & Closing 25% (Σ = 100%, Job Fit
 * weights only — Assessment no longer takes a per-position DISC/IST mix).
 */
class PositionSeeder extends Seeder
{
    public function run(): void
    {
        $position = Position::query()->firstOrCreate(
            ['name' => 'Sales Executive'],
            ['department' => 'sales', 'is_active' => true],
        );

        $weightsByCategoryName = [
            'COMMUNICATION' => 25,
            'SALES ORIENTATION' => 25,
            'PROBLEM SOLVING' => 25,
            'NEGOTIATION & CLOSING' => 25,
        ];

        foreach ($weightsByCategoryName as $categoryName => $weight) {
            $category = CompetencyCategory::query()
                ->where('department', 'sales')
                ->where('name', 'like', '%'.$categoryName)
                ->first();

            if (! $category) {
                continue;
            }

            $position->competencyCategoryWeights()->updateOrCreate(
                ['competency_category_id' => $category->id],
                ['weight' => $weight],
            );
        }

        $this->seedKasirAdminTokoTabinaco();
    }

    /**
     * Posisi Kasir/Admin Toko khusus instansi Tabinaco (soal dari
     * soal-kompetensi-kasir.xlsx, department `admin_toko`).
     */
    private function seedKasirAdminTokoTabinaco(): void
    {
        $instansi = Instansi::query()->whereRaw('LOWER(nama) = ?', ['tabinaco'])->first();

        if (! $instansi) {
            return;
        }

        $position = Position::query()->firstOrCreate(
            ['name' => 'Kasir/Admin Toko', 'instansi_id' => $instansi->id],
            ['department' => 'admin_toko', 'is_active' => true],
        );

        $categories = CompetencyCategory::query()
            ->where('department', 'admin_toko')
            ->get();

        foreach ($categories as $category) {
            $position->competencyCategoryWeights()->updateOrCreate(
                ['competency_category_id' => $category->id],
                ['weight' => round(100 / $categories->count(), 2)],
            );
        }
    }
}
