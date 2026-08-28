<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('disc_tests', function (Blueprint $table) {

            // Graph I: hasil konversi RW Most (raw score) per dimensi.
            $table->unsignedTinyInteger('most_graph_d')->default(0)->after('graph_c');
            $table->unsignedTinyInteger('most_graph_i')->default(0)->after('most_graph_d');
            $table->unsignedTinyInteger('most_graph_s')->default(0)->after('most_graph_i');
            $table->unsignedTinyInteger('most_graph_c')->default(0)->after('most_graph_s');

            // Graph II: hasil konversi RW Least (raw score) per dimensi.
            $table->unsignedTinyInteger('least_graph_d')->default(0)->after('most_graph_c');
            $table->unsignedTinyInteger('least_graph_i')->default(0)->after('least_graph_d');
            $table->unsignedTinyInteger('least_graph_s')->default(0)->after('least_graph_i');
            $table->unsignedTinyInteger('least_graph_c')->default(0)->after('least_graph_s');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('disc_tests', function (Blueprint $table) {

            $table->dropColumn([
                'most_graph_d',
                'most_graph_i',
                'most_graph_s',
                'most_graph_c',
                'least_graph_d',
                'least_graph_i',
                'least_graph_s',
                'least_graph_c',
            ]);

        });
    }
};
