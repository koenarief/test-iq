<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competency_questions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('competency_category_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->unsignedTinyInteger('question_number');

            $table->text('question_text');

            $table->timestamps();

            $table->unique(['competency_category_id', 'question_number'], 'competency_questions_category_number_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competency_questions');
    }
};
