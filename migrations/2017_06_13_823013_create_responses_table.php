<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Railroad\Railtracker\Services\ConfigService;

class CreateResponsesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            ConfigService::$tableResponses,
            function (Blueprint $table) {
                $table->bigIncrements('id');

                $table->bigInteger('request_id')->unsigned()->index();
                $table->bigInteger('status_code_id')->unsigned()->index();
                $table->bigInteger('error_id')->unsigned()->nullable()->index();

                $table->bigInteger('response_duration_ms')->unsigned();

                $table->dateTime('responded_on')->index();
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
        Schema::dropIfExists(ConfigService::$tableResponses);
    }
}
