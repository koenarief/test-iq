<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ist_test_sessions', function (Blueprint $table) {
            $table->string('dominance_type')->nullable()->after('iq_category');
            $table->json('subtest_scores')->nullable()->after('dominance_type');
        });
    }

    public function down(): void
    {
        Schema::table('ist_test_sessions', function (Blueprint $table) {
            $table->dropColumn([
                'total_sw',
                'iq_score',
                'iq_category',
                'dominance_type',
                'subtest_scores'
            ]);
        });
    }
};

