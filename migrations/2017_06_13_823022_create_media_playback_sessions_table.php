<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMediaPlaybackSessionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'railtracker_media_playback_sessions',
            function (Blueprint $table) {
                $table->bigIncrements('id');

                $table->string('media_id', 64)->index();

                $table->bigInteger('user_id')->unsigned()->nullable()->index();
                $table->string('type_id')->unsigned()->index();

                $table->integer('seconds_watched')->unsigned();
                $table->integer('current_second')->unsigned();
                $table->integer('length')->unsigned();

                $table->dateTime('started_on')->index();
                $table->dateTime('last_updated_on')->index();
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
        Schema::dropIfExists('railtracker_requests');
    }
}
