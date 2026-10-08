<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_assessments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('position_id')->nullable()->constrained()->nullOnDelete();

            $table->foreignId('disc_test_id')->nullable()->constrained('disc_tests')->nullOnDelete();
            $table->foreignId('competency_test_id')->nullable()->constrained('competency_tests')->nullOnDelete();
            $table->foreignId('ist_test_id')->nullable()->constrained('ist_tests')->nullOnDelete();

            // Assessment Score
            $table->decimal('disc_score', 5, 2)->nullable();
            $table->decimal('ist_score', 5, 2)->nullable();
            $table->decimal('competency_score', 5, 2)->nullable();
            $table->decimal('assessment_score', 5, 2)->nullable();

            $table->boolean('gate_disc_passed')->nullable();
            $table->boolean('gate_ist_passed')->nullable();
            $table->boolean('gate_competency_passed')->nullable();
            $table->boolean('gate_assessment_passed')->nullable();
            $table->boolean('gate_passed')->nullable();
            $table->timestamp('assessment_scored_at')->nullable();

            // Job Fit Score
            $table->decimal('job_fit_score', 5, 2)->nullable();
            $table->json('job_fit_breakdown')->nullable();
            $table->timestamp('job_fit_scored_at')->nullable();

            // Strength & Development Area
            $table->json('strength_development_breakdown')->nullable();

            // Interview Score (manual entry)
            $table->decimal('interview_communication', 5, 2)->nullable();
            $table->decimal('interview_motivation', 5, 2)->nullable();
            $table->decimal('interview_problem_solving', 5, 2)->nullable();
            $table->decimal('interview_job_knowledge', 5, 2)->nullable();
            $table->decimal('interview_attitude', 5, 2)->nullable();
            $table->decimal('interview_work_alignment', 5, 2)->nullable();
            $table->decimal('interview_score', 5, 2)->nullable();
            $table->text('interview_notes')->nullable();
            $table->foreignId('interviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('interview_scored_at')->nullable();

            // Final Score
            $table->decimal('final_score', 5, 2)->nullable();
            $table->string('decision', 24)->nullable();
            $table->timestamp('final_scored_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_assessments');
    }
};
