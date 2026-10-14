<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * konsep-hrd.pdf splits Interview into two independently-scored stages
 * (HRD then User) instead of one flat 6-aspect form. The old single-stage
 * columns are dropped and replaced with per-stage aspect columns, each
 * stage keeping its own actor/timestamp. The combined `interview_score`
 * and `interview_notes`/`interview_scored_at` columns are reused for the
 * HRD x40% + User x60% combined result.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidate_assessments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('interviewed_by');
            $table->dropColumn([
                'interview_communication',
                'interview_motivation',
                'interview_problem_solving',
                'interview_job_knowledge',
                'interview_attitude',
                'interview_work_alignment',
                'interview_notes',
            ]);
        });

        Schema::table('candidate_assessments', function (Blueprint $table) {
            // Interview HRD (Sikap, Komitmen, Kecocokan dengan perusahaan)
            $table->decimal('interview_hrd_attitude_ethics', 5, 2)->nullable()->after('job_fit_scored_at');
            $table->decimal('interview_hrd_communication', 5, 2)->nullable()->after('interview_hrd_attitude_ethics');
            $table->decimal('interview_hrd_motivation_goals', 5, 2)->nullable()->after('interview_hrd_communication');
            $table->decimal('interview_hrd_discipline_responsibility', 5, 2)->nullable()->after('interview_hrd_motivation_goals');
            $table->decimal('interview_hrd_commitment_readiness', 5, 2)->nullable()->after('interview_hrd_discipline_responsibility');
            $table->decimal('interview_hrd_adaptability_teamwork', 5, 2)->nullable()->after('interview_hrd_commitment_readiness');
            $table->decimal('interview_hrd_score', 5, 2)->nullable()->after('interview_hrd_adaptability_teamwork');
            $table->text('interview_hrd_notes')->nullable()->after('interview_hrd_score');
            $table->foreignId('interview_hrd_by')->nullable()->after('interview_hrd_notes')->constrained('users')->nullOnDelete();
            $table->timestamp('interview_hrd_scored_at')->nullable()->after('interview_hrd_by');

            // Interview User (kemampuan & kesiapan menjalankan tugas)
            $table->decimal('interview_user_task_understanding', 5, 2)->nullable()->after('interview_hrd_scored_at');
            $table->decimal('interview_user_work_competence', 5, 2)->nullable()->after('interview_user_task_understanding');
            $table->decimal('interview_user_problem_analysis', 5, 2)->nullable()->after('interview_user_work_competence');
            $table->decimal('interview_user_work_quality', 5, 2)->nullable()->after('interview_user_problem_analysis');
            $table->decimal('interview_user_initiative_independence', 5, 2)->nullable()->after('interview_user_work_quality');
            $table->decimal('interview_user_communication_teamwork', 5, 2)->nullable()->after('interview_user_initiative_independence');
            $table->decimal('interview_user_score', 5, 2)->nullable()->after('interview_user_communication_teamwork');
            $table->text('interview_user_notes')->nullable()->after('interview_user_score');
            $table->foreignId('interview_user_by')->nullable()->after('interview_user_notes')->constrained('users')->nullOnDelete();
            $table->timestamp('interview_user_scored_at')->nullable()->after('interview_user_by');

            // interview_score / interview_scored_at (already exist) now hold
            // the combined HRD x40% + User x60% result.
        });
    }

    public function down(): void
    {
        Schema::table('candidate_assessments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('interview_hrd_by');
            $table->dropConstrainedForeignId('interview_user_by');
            $table->dropColumn([
                'interview_hrd_attitude_ethics',
                'interview_hrd_communication',
                'interview_hrd_motivation_goals',
                'interview_hrd_discipline_responsibility',
                'interview_hrd_commitment_readiness',
                'interview_hrd_adaptability_teamwork',
                'interview_hrd_score',
                'interview_hrd_notes',
                'interview_hrd_scored_at',
                'interview_user_task_understanding',
                'interview_user_work_competence',
                'interview_user_problem_analysis',
                'interview_user_work_quality',
                'interview_user_initiative_independence',
                'interview_user_communication_teamwork',
                'interview_user_score',
                'interview_user_notes',
                'interview_user_scored_at',
            ]);
        });

        Schema::table('candidate_assessments', function (Blueprint $table) {
            $table->decimal('interview_communication', 5, 2)->nullable();
            $table->decimal('interview_motivation', 5, 2)->nullable();
            $table->decimal('interview_problem_solving', 5, 2)->nullable();
            $table->decimal('interview_job_knowledge', 5, 2)->nullable();
            $table->decimal('interview_attitude', 5, 2)->nullable();
            $table->decimal('interview_work_alignment', 5, 2)->nullable();
            $table->text('interview_notes')->nullable();
            $table->foreignId('interviewed_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }
};
