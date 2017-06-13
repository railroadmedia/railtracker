<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRequestAgentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'railtracker_request_agents',
            function (Blueprint $table) {
                $table->bigIncrements('id');

                $table->string('name', 180)->index();
                $table->string('browser', 64)->index();
                $table->string('browser_version', 32);

                $table->unique(['name', 'browser', 'browser_version']);
            }
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('railtracker_request_agents');
    }
}
