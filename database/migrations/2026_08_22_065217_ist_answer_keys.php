<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ist_answer_keys', function (Blueprint $table) {
            $table->id();
            $table->enum('subtest', ['SE', 'WA', 'AN', 'GE', 'RA', 'ZR', 'FA', 'WU', 'ME']);
            $table->integer('question_number');
            $table->text('correct_answer'); // Bisa string tunggal ("a", "27") atau JSON untuk kata kunci GE
            $table->integer('score_weight')->default(1); // 1 untuk pilihan ganda biasa, bervariasi untuk GE (0, 1, 2)
            $table->timestamps();

            $table->unique(['subtest', 'question_number']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('ist_answer_keys');
    }
};
