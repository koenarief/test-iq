<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tabel ini murni lookup/turunan (diisi ulang oleh
        // DiscGraphConversionSeeder), jadi aman dikosongkan sebelum restrukturisasi
        // kolom supaya baris placeholder lama tidak melanggar unique key baru.
        DB::table('disc_graph_conversions')->truncate();

        Schema::table('disc_graph_conversions', function (Blueprint $table) {

            $table->dropUnique(['change_score']);
            $table->dropColumn('change_score');

        });

        Schema::table('disc_graph_conversions', function (Blueprint $table) {

            // Tabel sumber norma (norma-disc.xlsx) berisi 3 tabel konversi
            // terpisah: 'most' & 'least' (RW 0-20) dan 'change' (Most-Least,
            // -22..22), masing-masing dengan nilai grafik berbeda per dimensi
            // D/I/S/C -> nilai raw_score saja tidak lagi cukup sebagai kunci.
            $table->string('graph_type', 10)->after('id');
            $table->string('dimension', 1)->after('graph_type');
            $table->smallInteger('raw_score')->after('dimension');

            $table->unique(['graph_type', 'dimension', 'raw_score']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('disc_graph_conversions', function (Blueprint $table) {

            $table->dropUnique(['graph_type', 'dimension', 'raw_score']);
            $table->dropColumn(['graph_type', 'dimension', 'raw_score']);

        });

        Schema::table('disc_graph_conversions', function (Blueprint $table) {

            $table->tinyInteger('change_score')->unique();

        });
    }
};
