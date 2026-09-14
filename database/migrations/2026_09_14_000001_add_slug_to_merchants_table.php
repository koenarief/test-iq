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
            $table->string('slug')->nullable()->after('name');
        });

        $usedSlugs = [];

        DB::table('merchants')->orderBy('id')->lazy()->each(function (object $merchant) use (&$usedSlugs) {
            $base = Str::slug($merchant->name) ?: 'merchant';
            $slug = $base;
            $suffix = 2;

            while (in_array($slug, $usedSlugs, true)) {
                $slug = "{$base}-{$suffix}";
                $suffix++;
            }

            $usedSlugs[] = $slug;

            DB::table('merchants')->where('id', $merchant->id)->update(['slug' => $slug]);
        });

        Schema::table('merchants', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->unique()->change();
        });
    }

    public function down(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
