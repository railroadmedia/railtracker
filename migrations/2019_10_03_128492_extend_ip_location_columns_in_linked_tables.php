<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ExtendIpLocationColumnsInLinkedTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $tablePrefix = config('railtracker.table_prefix') ?? 'railtracker3_';

        Schema::disableForeignKeyConstraints();

        Schema::table($tablePrefix . 'ip_latitudes', function (Blueprint $table) {
            $table->decimal('ip_latitude', 11, 8)->change();
        });

        Schema::table($tablePrefix . 'ip_longitudes', function (Blueprint $table) {
            $table->decimal('ip_longitude', 11, 8)->change();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $tablePrefix = config('railtracker.table_prefix') ?? 'railtracker3_';

        Schema::disableForeignKeyConstraints();

        DB::table($tablePrefix . 'ip_latitudes')->truncate();
        DB::table($tablePrefix . 'ip_longitudes')->truncate();

        Schema::table($tablePrefix . 'ip_latitudes', function (Blueprint $table) {
            $table->decimal('ip_latitude', 10, 8)->change();
        });

        Schema::table($tablePrefix . 'ip_longitudes', function (Blueprint $table) {
            $table->decimal('ip_longitude', 10, 8)->change();
        });

        Schema::enableForeignKeyConstraints();
    }
}
