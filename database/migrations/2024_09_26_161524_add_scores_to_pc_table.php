<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddScoresToPcTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pc', function (Blueprint $table) {
            $table->decimal('gamer_score', 10, 2)->nullable();
            $table->decimal('workstation_score', 10, 2)->nullable();
            $table->decimal('desktop_score', 10, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pc', function (Blueprint $table) {
            $table->dropColumn(['gamer_score', 'workstation_score', 'desktop_score']);
        });
    }
}

