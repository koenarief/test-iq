<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ist_test_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('participant_number')->unique();
            $table->string('name');
            $table->date('birth_date');
            $table->date('test_date');
            $table->integer('age'); // Dihitung otomatis dari birth_date vs test_date
            $table->enum('gender', ['MALE', 'FEMALE'])->default('MALE'); // Diperlukan untuk norma FA/WU jika ada perbedaan
            $table->string('test_purpose')->nullable();
            
            // Skor Mentah (Raw Score - RW) per Subtes
            $table->integer('rw_se')->default(0);
            $table->integer('rw_wa')->default(0);
            $table->integer('rw_an')->default(0);
            $table->integer('rw_ge')->default(0);
            $table->integer('rw_ra')->default(0);
            $table->integer('rw_zr')->default(0);
            $table->integer('rw_fa')->default(0);
            $table->integer('rw_wu')->default(0);
            $table->integer('rw_me')->default(0);
            $table->integer('total_rw')->default(0);

            // Skor Standar (Standard Score - SW) per Subtes
            $table->integer('sw_se')->default(0);
            $table->integer('sw_wa')->default(0);
            $table->integer('sw_an')->default(0);
            $table->integer('sw_ge')->default(0);
            $table->integer('sw_ra')->default(0);
            $table->integer('sw_zr')->default(0);
            $table->integer('sw_fa')->default(0);
            $table->integer('sw_wu')->default(0);
            $table->integer('sw_me')->default(0);
            $table->integer('total_sw')->default(0);

            // Hasil Akhir
            $table->integer('iq_score')->nullable();
            $table->string('iq_category')->nullable(); // High, Average, Mentally Defective, dll.
            $table->string('dominance_profile')->nullable(); // M-Dominant, W-Dominant, Balanced
            
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('ist_test_sessions');
    }
};
