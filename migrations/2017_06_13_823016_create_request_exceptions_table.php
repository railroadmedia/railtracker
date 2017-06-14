<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Railroad\Railtracker\Services\ConfigService;

class CreateRequestExceptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            ConfigService::$tableRequestExceptions,
            function (Blueprint $table) {
                $table->bigIncrements('id');

                $table->bigInteger('exception_id')->unsigned()->index();
                $table->bigInteger('request_id')->unsigned()->index();

                $table->bigInteger('created_on_ms')->unsigned()->index();
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
        Schema::dropIfExists(ConfigService::$tableRequestExceptions);
    }
}
