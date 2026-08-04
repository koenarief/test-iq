<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ist_test_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ist_test_subtest_id')
                ->constrained('ist_test_subtests')
                ->cascadeOnDelete();
            $table->foreignId('source_question_id')
                ->nullable()
                ->constrained('ist_questions')
                ->nullOnDelete();
            $table->unsignedSmallInteger('display_order');
            $table->string('answer_type', 32);
            $table->json('question_snapshot');
            $table->json('options_snapshot')->nullable();
            $table->json('answer_key_snapshot');
            $table->decimal('max_score', 8, 4);
            $table->timestamps();

            $table->unique(
                ['ist_test_subtest_id', 'display_order'],
                'ist_test_questions_subtest_order_unique'
            );
            $table->index('source_question_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ist_test_questions');
    }
};
