<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Railroad\Railtracker\Services\ConfigService;

class CreateGeoipTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            ConfigService::$tableGeoIP,
            function (Blueprint $table) {
                $table->bigIncrements('id');

                $table->double('latitude')->nullable()->index();
                $table->double('longitude')->nullable()->index();

                $table->string('country_code', 2)->nullable()->index();
                $table->string('country_name', 84)->nullable()->index();
                $table->string('region', 2)->nullable();
                $table->string('city', 50)->nullable()->index();
                $table->string('postal_code', 20)->nullable();
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
        Schema::dropIfExists(ConfigService::$tableGeoIP);
    }
}
