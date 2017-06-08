<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTrackerAgentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'tracker_agents',
            function (Blueprint $table) {
                $table->bigIncrements('id');

                $table->mediumText('name')->unique();
                $table->string('browser')->index();
                $table->string('browser_version');

                $table->unique(['name', 'browser', 'browser_version']);

                $table->timestamp('created_at')->index();
                $table->timestamp('updated_at')->index();
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
        Schema::dropIfExists('tracker_agents');
    }
}
