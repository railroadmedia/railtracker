<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateGeoIpFixTempLibraryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // requests tables =============================================================================================

        $tablePrefix = config('railtracker.table_prefix');
        $tableName = $tablePrefix . 'geo_ip_intermediary_lib';

        Schema::create(
            $tableName,
            function (Blueprint $table) {
                $table->charset = config('railtracker.charset');
                $table->collation = config('railtracker.collation');

                $table->bigIncrements('id');

                $table->string('ip_address', 128)->index()->nullable();
                $table->decimal('ip_latitude', 11, 8)->index()->nullable();
                $table->decimal('ip_longitude', 11, 8)->index()->nullable();
                $table->string('ip_country_code', 6)->index()->nullable();
                $table->string('ip_country_name', 128)->index()->nullable();
                $table->string('ip_region', 128)->index()->nullable();
                $table->string('ip_city', 128)->index()->nullable();
                $table->string('ip_postal_zip_code', 16)->index()->nullable();
                $table->string('ip_timezone', 64)->index()->nullable();
                $table->string('ip_currency', 16)->index()->nullable();

                $table->boolean('private')->nullable();
                $table->boolean('failed')->nullable();
                $table->dateTime('created', 5)->index();
                $table->dateTime('filled', 5)->index()->nullable();
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
        $tablePrefix = config('railtracker.table_prefix');
        Schema::dropIfExists($tablePrefix . 'geo_ip_intermediary_lib');
    }
}
