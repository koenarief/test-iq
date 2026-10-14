<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Competency no longer feeds the Assessment formula or its gate
 * (konsep-hrd.pdf: ASSESSMENT = DISC x50% + IST x50%; Competency only
 * feeds Job Fit now), so a Competency-specific assessment gate no longer
 * means anything.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidate_assessments', function (Blueprint $table) {
            $table->dropColumn('gate_competency_passed');
        });
    }

    public function down(): void
    {
        Schema::table('candidate_assessments', function (Blueprint $table) {
            $table->boolean('gate_competency_passed')->nullable()->after('gate_ist_passed');
        });
    }
};
