<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competency_tests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('merchant_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('participant_name');

            $table->unsignedTinyInteger('age');

            $table->enum('gender', ['L', 'P']);

            $table->string('department', 32);

            $table->enum('status', [
                'draft',
                'in_progress',
                'completed',
            ])->default('draft');

            $table->timestamp('started_at')->nullable();

            $table->timestamp('finished_at')->nullable();

            $table->decimal('total_score', 5, 2)->nullable();

            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competency_tests');
    }
};
