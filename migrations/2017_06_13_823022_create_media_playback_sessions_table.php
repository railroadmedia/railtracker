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

                $table->string('uuid', 64)->unique()->index();
                $table->bigInteger('user_id')->unsigned()->nullable()->index();
                $table->string('media_id')->unsigned()->index();
                $table->string('media_type')->unsigned()->index();
                $table->bigInteger('route_id')->unsigned()->nullable()->index();
                $table->bigInteger('device_id')->unsigned()->index();
                $table->bigInteger('agent_id')->unsigned()->index();
                $table->bigInteger('referer_url_id')->unsigned()->nullable()->index();
                $table->bigInteger('language_id')->unsigned()->index();
                $table->bigInteger('geoip_id')->unsigned()->nullable()->index();

                $table->string('client_ip', 36)->index();

                $table->boolean('is_robot')->index();

                $table->bigInteger('request_duration_ms')->nullable();

                $table->integer('request_time')->index();
                $table->timestamp('created_at')->nullable()->index();
                $table->timestamp('updated_at')->nullable()->index();
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
