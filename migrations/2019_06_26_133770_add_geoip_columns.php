<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Railroad\Railtracker\Services\ConfigService;

class AddGeoIPColumns extends Migration
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
                $table->string('ip_address')->nullable();
                $table->string('timezone')->nullable();
                $table->string('currency')->nullable();
                $table->string('hash')->nullable()->unique();
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
                $table->dropColumn('ip_address');
                $table->dropColumn('timezone');
                $table->dropColumn('currency');
                $table->dropColumn('hash');
            }
        );
    }
}
