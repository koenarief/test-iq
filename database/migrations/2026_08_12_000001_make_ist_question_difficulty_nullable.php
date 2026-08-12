<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('ist_questions', 'difficulty')) {
            DB::statement(<<<'SQL'
                ALTER TABLE ist_questions
                MODIFY difficulty VARCHAR(10) NULL DEFAULT NULL
                SQL);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('ist_questions', 'difficulty')) {
            DB::table('ist_questions')
                ->whereNull('difficulty')
                ->update(['difficulty' => 'medium']);

            DB::statement(<<<'SQL'
                ALTER TABLE ist_questions
                MODIFY difficulty VARCHAR(10) NOT NULL DEFAULT 'medium'
                SQL);
        }
    }
};
