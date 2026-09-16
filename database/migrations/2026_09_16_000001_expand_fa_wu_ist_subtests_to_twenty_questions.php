<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * FA and WU were seeded with only 10/12 scored questions (placeholder
     * counts from before real per-question artwork existed), even though
     * ist_norm_subtests already carries the full RW 0-20 norm table for both
     * subtests. This aligns question_count/duration with a real 20-question
     * bank for each (see database/data/ist-fa-wu-original), scaling duration
     * proportionally the same way the SE/WA/AN expansion migration did.
     */
    public function up(): void
    {
        DB::table('ist_subtests')->where('code', 'FA')->update([
            'question_count' => 20,
            'duration_seconds' => 480,
            'answering_seconds' => 480,
        ]);

        DB::table('ist_subtests')->where('code', 'WU')->update([
            'question_count' => 20,
            'duration_seconds' => 600,
            'answering_seconds' => 600,
        ]);
    }

    public function down(): void
    {
        DB::table('ist_subtests')->where('code', 'FA')->update([
            'question_count' => 10,
            'duration_seconds' => 240,
            'answering_seconds' => 240,
        ]);

        DB::table('ist_subtests')->where('code', 'WU')->update([
            'question_count' => 12,
            'duration_seconds' => 360,
            'answering_seconds' => 360,
        ]);
    }
};
