<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ist_test_subtests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ist_test_id')
                ->constrained('ist_tests')
                ->cascadeOnDelete();
            $table->foreignId('ist_subtest_id')
                ->constrained('ist_subtests')
                ->restrictOnDelete();
            $table->unsignedTinyInteger('sequence');
            $table->string('status', 24)->default('pending');
            $table->unsignedSmallInteger('question_count');
            $table->timestamp('instruction_viewed_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('memorization_started_at')->nullable();
            $table->timestamp('memorization_ends_at')->nullable();
            $table->timestamp('answering_started_at')->nullable();
            $table->timestamp('answering_ends_at')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->string('finalized_reason', 24)->nullable();
            $table->decimal('awarded_score', 10, 4)->default(0);
            $table->decimal('max_score', 10, 4)->default(0);
            $table->unsignedSmallInteger('correct_count')->default(0);
            $table->unsignedSmallInteger('partial_count')->default(0);
            $table->unsignedSmallInteger('wrong_count')->default(0);
            $table->unsignedSmallInteger('blank_count')->default(0);
            $table->decimal('percentage', 6, 3)->default(0);
            $table->timestamp('last_autosaved_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['ist_test_id', 'ist_subtest_id'],
                'ist_test_subtests_test_subtest_unique'
            );
            $table->unique(
                ['ist_test_id', 'sequence'],
                'ist_test_subtests_test_sequence_unique'
            );
            $table->index(['status', 'answering_ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ist_test_subtests');
    }
};
