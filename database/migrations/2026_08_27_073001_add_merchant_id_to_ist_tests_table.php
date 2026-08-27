<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ist_tests', function (Blueprint $table) {
            $table->foreignId('merchant_id')
                ->nullable()
                ->after('user_id')
                ->constrained('merchants')
                ->nullOnDelete();

            $table->index('merchant_id');
        });
    }

    public function down(): void
    {
        Schema::table('ist_tests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('merchant_id');
        });
    }
};
