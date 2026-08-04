<?php

namespace Database\Seeders\Ist;

use App\Models\Ist\IstSubtest;
use App\Support\Ist\IstSubtestCatalog;
use Illuminate\Database\Seeder;

class IstSubtestSeeder extends Seeder
{
    public function run(): void
    {
        foreach (IstSubtestCatalog::all() as $definition) {
            IstSubtest::updateOrCreate(
                ['code' => $definition['code']],
                [...$definition, 'is_active' => true]
            );
        }
    }
}
