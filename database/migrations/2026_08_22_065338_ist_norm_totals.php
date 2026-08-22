<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ist_norm_totals', function (Blueprint $table) {
            $table->id();
            $table->integer('min_age');
            $table->integer('max_age');
            $table->integer('total_sw'); // Sum of SW subtests
            $table->integer('iq_score');
            $table->string('iq_category');
            $table->timestamps();

            $table->index(['min_age', 'max_age', 'total_sw'], 'idx_norm_total_lookup');
        });
    }

    public function down(): void {
        Schema::dropIfExists('ist_norm_totals');
    }
};
