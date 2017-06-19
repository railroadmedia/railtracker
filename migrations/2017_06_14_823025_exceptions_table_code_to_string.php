<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Railroad\Railtracker\Services\ConfigService;

class ExceptionsTableCodeToString extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(
            ConfigService::$tableExceptions,
            function (Blueprint $table) {
                $table->string('code', 64)->change();
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
        Schema::table(
            ConfigService::$tableExceptions,
            function (Blueprint $table) {
                $table->bigInteger('code')->unsigned()->change();
            }
        );
    }
}
