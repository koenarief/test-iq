<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('ist_questions', 'difficulty')) {
            Schema::table('ist_questions', function (Blueprint $table) {
                $table->string('difficulty', 10)
                    ->default('medium')
                    ->after('max_score');
            });
        }

        if (! Schema::hasColumn('ist_test_questions', 'difficulty')) {
            Schema::table('ist_test_questions', function (Blueprint $table) {
                $table->string('difficulty', 10)
                    ->default('medium')
                    ->after('max_score');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('ist_test_questions', 'difficulty')) {
            Schema::table('ist_test_questions', function (Blueprint $table) {
                $table->dropColumn('difficulty');
            });
        }

        if (Schema::hasColumn('ist_questions', 'difficulty')) {
            Schema::table('ist_questions', function (Blueprint $table) {
                $table->dropColumn('difficulty');
            });
        }
    }
};