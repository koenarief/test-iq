<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Question bank for SE/WA/AN already has 20 active scored questions each
     * (12 from the approved final dataset plus 8 added later through the
     * admin panel), but ist_subtests was still configured for 12. That
     * mismatch made IstQuestionSnapshotService reject every new test start
     * with "expected 12, found 20". This aligns the configured count/duration
     * with the actual active question bank.
     */
    public function up(): void
    {
        DB::table('ist_subtests')
            ->whereIn('code', ['SE', 'WA', 'AN'])
            ->update([
                'question_count' => 20,
                'duration_seconds' => 400,
                'answering_seconds' => 400,
            ]);
    }

    public function down(): void
    {
        DB::table('ist_subtests')
            ->whereIn('code', ['SE', 'WA', 'AN'])
            ->update([
                'question_count' => 12,
                'duration_seconds' => 240,
                'answering_seconds' => 240,
            ]);
    }
};
