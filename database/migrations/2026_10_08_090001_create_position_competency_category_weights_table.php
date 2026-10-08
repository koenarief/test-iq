<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('position_competency_category_weights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('position_id')->constrained()->cascadeOnDelete();
            $table->foreignId('competency_category_id')
                ->constrained(indexName: 'pos_comp_cat_weight_category_id_foreign')
                ->cascadeOnDelete();
            $table->decimal('weight', 5, 2);
            $table->timestamps();

            $table->unique(['position_id', 'competency_category_id'], 'position_category_weight_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('position_competency_category_weights');
    }
};
