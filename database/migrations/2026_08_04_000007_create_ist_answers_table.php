<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ist_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ist_test_question_id')
                ->unique()
                ->constrained('ist_test_questions')
                ->cascadeOnDelete();
            $table->string('selected_option_key', 10)->nullable();
            $table->decimal('numeric_answer', 20, 6)->nullable();
            $table->decimal('awarded_score', 8, 4)->nullable();
            $table->string('outcome', 16)->nullable();
            $table->unsignedInteger('client_revision')->default(0);
            $table->timestamp('saved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ist_answers');
    }
};
