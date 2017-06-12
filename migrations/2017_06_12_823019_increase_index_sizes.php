<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('railtracker_agents', function (Blueprint $table) {
            $table->string('name', 180)->change();
        });
        Schema::table('railtracker_domains', function (Blueprint $table) {
            $table->string('name', 180)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('railtracker_agents', function (Blueprint $table) {
            $table->string('name', 140)->change();
        });
    }
}
