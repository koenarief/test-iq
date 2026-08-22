<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ist_norm_subtests', function (Blueprint $table) {
            $table->id();
            $table->integer('min_age'); // misal: 13
            $table->integer('max_age'); // misal: 13 (atau 99 untuk <=24 thn)
            $table->enum('subtest', ['SE', 'WA', 'AN', 'GE', 'RA', 'ZR', 'FA', 'WU', 'ME']);
            $table->integer('raw_score'); // 0 s/d 20
            $table->integer('standard_score'); // Nilai SW hasil konversi
            $table->timestamps();

            $table->index(['subtest', 'min_age', 'max_age', 'raw_score'], 'idx_norm_lookup');
        });
    }

    public function down(): void {
        Schema::dropIfExists('ist_norm_subtests');
    }
};

