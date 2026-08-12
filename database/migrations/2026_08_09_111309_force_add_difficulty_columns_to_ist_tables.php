<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('ist_questions', 'difficulty')) {
            DB::statement("
                ALTER TABLE ist_questions
                ADD COLUMN difficulty VARCHAR(10) NOT NULL DEFAULT 'medium'
                AFTER max_score
            ");
        }

        if (! Schema::hasColumn('ist_test_questions', 'difficulty')) {
            DB::statement("
                ALTER TABLE ist_test_questions
                ADD COLUMN difficulty VARCHAR(10) NOT NULL DEFAULT 'medium'
                AFTER max_score
            ");
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('ist_test_questions', 'difficulty')) {
            DB::statement("
                ALTER TABLE ist_test_questions
                DROP COLUMN difficulty
            ");
        }

        if (Schema::hasColumn('ist_questions', 'difficulty')) {
            DB::statement("
                ALTER TABLE ist_questions
                DROP COLUMN difficulty
            ");
        }
    }
};