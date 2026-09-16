<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competency_categories', function (Blueprint $table) {
            $table->id();

            $table->string('department', 32);

            $table->string('name');

            $table->unsignedTinyInteger('order')->default(1);

            $table->timestamps();

            $table->unique(['department', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competency_categories');
    }
};
