<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->foreignId('instansi_id')->nullable()->after('name')
                ->constrained('instansi')->nullOnDelete();
        });

        // Hubungkan merchant yang namanya sama dengan instansi (mis. Tabinaco).
        foreach (DB::table('instansi')->get(['id', 'nama']) as $instansi) {
            DB::table('merchants')
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($instansi->nama)])
                ->update(['instansi_id' => $instansi->id]);
        }
    }

    public function down(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('instansi_id');
        });
    }
};
