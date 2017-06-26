<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Railroad\Railtracker\Services\ConfigService;

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
            ConfigService::$tableMediaPlaybackSessions,
            function (Blueprint $table) {
                $table->bigIncrements('id');

                $table->string('uuid', 64)->unique()->nullable()->index();

                $table->string('media_id', 64)->index();
                $table->integer('media_length_seconds')->unsigned();

                $table->bigInteger('user_id')->unsigned()->nullable()->index();
                $table->bigInteger('type_id')->unsigned()->index();

                $table->bigInteger('seconds_played')->unsigned();
                $table->bigInteger('current_second')->unsigned();

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
        Schema::dropIfExists(ConfigService::$tableMediaPlaybackSessions);
    }
}
