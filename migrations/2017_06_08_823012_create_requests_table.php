<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Railroad\Railtracker\Services\ConfigService;

class CreateRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            ConfigService::$tableRequests,
            function (Blueprint $table) {
                $table->bigIncrements('id');

                $table->string('uuid', 64)->unique()->index();

                $table->bigInteger('user_id')->unsigned()->nullable()->index();
                $table->string('cookie_id', 64)->nullable()->index();
                $table->bigInteger('url_id')->unsigned()->nullable()->index();
                $table->bigInteger('route_id')->unsigned()->nullable()->index();
                $table->bigInteger('device_id')->unsigned()->index();
                $table->bigInteger('agent_id')->unsigned()->index();
                $table->bigInteger('method_id')->unsigned()->index();
                $table->bigInteger('referer_url_id')->unsigned()->nullable()->index();
                $table->bigInteger('language_id')->unsigned()->index();
                $table->bigInteger('geoip_id')->unsigned()->nullable()->index();

                $table->string('client_ip', 64)->index();

                $table->boolean('is_robot')->index();

                $table->dateTime('requested_on')->index();
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
        Schema::dropIfExists(ConfigService::$tableRequests);
    }
}
