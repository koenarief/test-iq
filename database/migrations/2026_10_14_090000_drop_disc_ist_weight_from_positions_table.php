<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Assessment is now a fixed DISC x50% + IST x50% formula (konsep-hrd.pdf),
 * no longer configurable per position, so these columns are dropped.
 * Job Fit weighting is now carried entirely by
 * position_competency_category_weights.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('positions', function (Blueprint $table) {
            $table->dropColumn(['disc_weight', 'ist_weight']);
        });
    }

    public function down(): void
    {
        Schema::table('positions', function (Blueprint $table) {
            $table->decimal('disc_weight', 5, 2)->default(0)->after('department');
            $table->decimal('ist_weight', 5, 2)->default(0)->after('disc_weight');
        });
    }
};
