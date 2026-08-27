<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->uuid('public_id')->nullable()->after('id');
        });

        DB::table('merchants')->whereNull('public_id')->orderBy('id')->lazy()->each(function (object $merchant) {
            DB::table('merchants')
                ->where('id', $merchant->id)
                ->update(['public_id' => (string) Str::uuid()]);
        });

        Schema::table('merchants', function (Blueprint $table) {
            $table->uuid('public_id')->nullable(false)->unique()->change();
        });
    }

    public function down(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->dropUnique(['public_id']);
            $table->dropColumn('public_id');
        });
    }
};
