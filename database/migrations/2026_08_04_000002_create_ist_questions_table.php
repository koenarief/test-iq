<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ist_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ist_subtest_id')
                ->constrained('ist_subtests')
                ->restrictOnDelete();
            $table->unsignedSmallInteger('question_number');
            $table->unsignedSmallInteger('display_order');
            $table->string('kind', 16)->default('scored');
            $table->string('answer_type', 32);
            $table->longText('prompt')->nullable();
            $table->string('image_disk')->nullable();
            $table->string('image_path')->nullable();
            $table->string('image_alt')->nullable();
            $table->longText('example_explanation')->nullable();
            $table->decimal('numeric_answer_key', 20, 6)->nullable();
            $table->decimal('max_score', 8, 4)->default(1);
            $table->unsignedInteger('version')->default(1);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(
                ['ist_subtest_id', 'kind', 'question_number'],
                'ist_questions_subtest_kind_number_unique'
            );
            $table->index(
                ['ist_subtest_id', 'kind', 'is_active'],
                'ist_questions_active_lookup_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ist_questions');
    }
};
