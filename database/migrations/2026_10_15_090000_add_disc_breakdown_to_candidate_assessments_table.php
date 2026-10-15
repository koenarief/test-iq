<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DISC score is now a per-position DISC Fit Score (bobot-disc.pdf); the
 * weights/graph values used are kept here for display on the Rekap page.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidate_assessments', function (Blueprint $table) {
            $table->json('disc_breakdown')->nullable()->after('disc_score');
        });
    }

    public function down(): void
    {
        Schema::table('candidate_assessments', function (Blueprint $table) {
            $table->dropColumn('disc_breakdown');
        });
    }
};
