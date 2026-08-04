<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ist_question_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ist_question_id')
                ->constrained('ist_questions')
                ->cascadeOnDelete();
            $table->string('option_key', 10);
            $table->text('option_text')->nullable();
            $table->string('image_disk')->nullable();
            $table->string('image_path')->nullable();
            $table->string('image_alt')->nullable();
            $table->unsignedSmallInteger('display_order');
            $table->boolean('is_correct')->default(false);
            $table->decimal('score_value', 8, 4)->default(0);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->unique(
                ['ist_question_id', 'option_key'],
                'ist_question_options_question_key_unique'
            );
            $table->unique(
                ['ist_question_id', 'display_order'],
                'ist_question_options_question_order_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ist_question_options');
    }
};
