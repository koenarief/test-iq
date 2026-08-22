<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ist_user_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_session_id')->constrained('ist_test_sessions')->onDelete('cascade');
            $table->enum('subtest', ['SE', 'WA', 'AN', 'GE', 'RA', 'ZR', 'FA', 'WU', 'ME']);
            $table->integer('question_number');
            $table->text('user_answer')->nullable();
            $table->integer('earned_score')->default(0); // Hasil koreksi otomatis per nomor
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('ist_user_responses');
    }
};