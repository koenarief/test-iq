<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competency_answers', function (Blueprint $table) {
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

            $table->string('selected_label', 1);

            $table->unsignedTinyInteger('points');

            $table->timestamps();

            $table->unique(['competency_test_id', 'competency_question_id'], 'competency_answers_test_question_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competency_answers');
    }
};
