<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competency_test_questions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('competency_test_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('competency_question_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('competency_category_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->unsignedTinyInteger('order');

            $table->timestamps();

            $table->unique(['competency_test_id', 'competency_question_id'], 'competency_test_questions_test_question_unique');
            $table->unique(['competency_test_id', 'order'], 'competency_test_questions_test_order_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competency_test_questions');
    }
};
