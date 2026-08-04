<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ist_tests', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('access_token_hash', 64)->nullable()->unique();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('participant_name');
            $table->unsignedTinyInteger('age');
            $table->string('gender', 1);
            $table->string('status', 24)->default('draft');
            $table->unsignedTinyInteger('current_subtest_sequence')->default(1);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->decimal('total_internal_score', 6, 3)->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ist_tests');
    }
};
