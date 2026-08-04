<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ist_subtests', function (Blueprint $table) {
            $table->id();
            $table->string('code', 2)->unique();
            $table->string('name');
            $table->unsignedTinyInteger('sequence')->unique();
            $table->unsignedSmallInteger('question_count');
            $table->string('default_answer_type', 32);
            $table->longText('instruction_content')->nullable();
            $table->longText('memorization_content')->nullable();
            $table->unsignedSmallInteger('duration_seconds');
            $table->unsignedSmallInteger('memorization_seconds')->default(0);
            $table->unsignedSmallInteger('answering_seconds');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ist_subtests');
    }
};
