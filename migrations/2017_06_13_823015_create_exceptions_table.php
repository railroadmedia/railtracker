<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Railroad\Railtracker\Services\ConfigService;

class CreateExceptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            ConfigService::$tableExceptions,
            function (Blueprint $table) {
                $table->bigIncrements('id');

                $table->string('code', 64)->index();
                $table->bigInteger('line')->unsigned()->index();
                $table->string('exception_class', 1064);
                $table->string('file', 1064);
                $table->text('message');
                $table->text('trace');
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
        Schema::dropIfExists(ConfigService::$tableExceptions);
    }
}
