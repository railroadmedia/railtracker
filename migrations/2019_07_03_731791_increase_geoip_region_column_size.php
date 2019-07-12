<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Railroad\Railtracker\Services\ConfigService;

class IncreaseGeoipRegionColumnSize extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(
            ConfigService::$tableGeoIP,
            function (Blueprint $table) {
                $table->string('region', 160)->change();
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
            ConfigService::$tableGeoIP,
            function (Blueprint $table) {
                $table->string('region', 2)->change();
            }
        );
    }
}
