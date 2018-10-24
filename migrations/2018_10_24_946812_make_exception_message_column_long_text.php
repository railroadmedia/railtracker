<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Railroad\Railtracker\Services\ConfigService;

class MakeExceptionMessageColumnLongText extends Migration
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
                $table->longText('message')->change();
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
                $table->text('message')->change();
            }
        );
    }
}
