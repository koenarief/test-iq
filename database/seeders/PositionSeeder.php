<?php

namespace Database\Seeders;

use App\Models\CompetencyCategory;
use App\Models\Position;
use Illuminate\Database\Seeder;

/**
 * Seeds the exact Sales Executive example from score.md so Job Fit Score
 * can be exercised end-to-end. Category weights target the sales
 * department's existing categories (COMMUNICATION, SALES ORIENTATION,
 * PROBLEM SOLVING), which match score.md's example verbatim.
 */
class PositionSeeder extends Seeder
{
    public function run(): void
    {
        if (Position::query()->where('name', 'Sales Executive')->exists()) {
            return;
        }

        $position = Position::create([
            'name' => 'Sales Executive',
            'department' => 'sales',
            'disc_weight' => 15,
            'ist_weight' => 15,
            'is_active' => true,
        ]);

        $weightsByCategoryName = [
            'SALES ORIENTATION' => 30,
            'COMMUNICATION' => 25,
            'PROBLEM SOLVING' => 15,
        ];

        foreach ($weightsByCategoryName as $categoryName => $weight) {
            $category = CompetencyCategory::query()
                ->where('department', 'sales')
                ->where('name', $categoryName)
                ->first();

            if (! $category) {
                continue;
            }

            $position->competencyCategoryWeights()->create([
                'competency_category_id' => $category->id,
                'weight' => $weight,
            ]);
        }
    }
}
