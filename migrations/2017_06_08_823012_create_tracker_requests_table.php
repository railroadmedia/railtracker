<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTrackerRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'tracker_requests',
            function (Blueprint $table) {
                $table->bigIncrements('id');

                $table->string('uuid')->unique()->index();

                $table->bigInteger('user_id')->unsigned()->nullable()->index();
                $table->bigInteger('url_id')->unsigned()->index();
                $table->bigInteger('route_id')->unsigned()->nullable()->index();
                $table->bigInteger('device_id')->unsigned()->index();
                $table->bigInteger('agent_id')->unsigned()->index();
                $table->bigInteger('referer_url_id')->unsigned()->nullable()->index();
                $table->bigInteger('language_id')->unsigned()->index();
                $table->bigInteger('geoip_id')->unsigned()->nullable()->index();

                $table->string('client_ip')->index();

                $table->boolean('is_robot')->index();

                $table->bigInteger('request_duration_ms')->nullable();

                $table->timestamp('request_time')->index();
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
        Schema::dropIfExists('tracker_requests');
    }
}
