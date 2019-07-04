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
                $table->string('ip_address', 191)->nullable();
                $table->string('timezone', 128)->nullable();
                $table->string('currency', 3)->nullable();
                $table->string('hash', 128)->nullable()->unique();
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
