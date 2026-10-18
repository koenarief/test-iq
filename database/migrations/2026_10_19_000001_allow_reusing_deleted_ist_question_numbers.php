<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ist_questions', function (Blueprint $table) {
            // NULL for archived rows: only non-deleted questions reserve a number.
            $table->unsignedSmallInteger('live_question_number')->nullable()
                ->virtualAs('CASE WHEN deleted_at IS NULL THEN question_number ELSE NULL END');
            $table->unique(['ist_subtest_id', 'kind', 'live_question_number'], 'ist_questions_live_number_unique');
        });
        Schema::table('ist_questions', function (Blueprint $table) {
            $table->dropUnique('ist_questions_subtest_kind_number_unique');
        });
    }

    public function down(): void
    {
        if (DB::table('ist_questions')->select('ist_subtest_id', 'kind', 'question_number')
            ->groupBy('ist_subtest_id', 'kind', 'question_number')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException('Cannot restore the old IST unique index: reused numbers exist in archived questions. Keep this migration or resolve duplicates before rollback.');
        }
        Schema::table('ist_questions', function (Blueprint $table) {
            $table->unique(['ist_subtest_id', 'kind', 'question_number'], 'ist_questions_subtest_kind_number_unique');
            $table->dropUnique('ist_questions_live_number_unique');
        });
        Schema::table('ist_questions', function (Blueprint $table) {
            $table->dropColumn('live_question_number');
        });
    }
};
