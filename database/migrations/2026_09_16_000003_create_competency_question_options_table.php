<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competency_question_options', function (Blueprint $table) {
            $table->id();

            $table->foreignId('competency_question_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('label', 1);

            $table->text('option_text');

            $table->unsignedTinyInteger('points');

            $table->timestamps();

            $table->unique(['competency_question_id', 'label'], 'competency_question_options_question_label_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competency_question_options');
    }
};
