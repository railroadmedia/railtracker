<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ExtendIpLocationColumnsInRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $tablePrefix = config('railtracker.table_prefix') ?? 'railtracker3_';
        $tableName = $tablePrefix . 'requests';

        Schema::disableForeignKeyConstraints();

        Schema::table($tableName, function (Blueprint $table) {
            $table->decimal('ip_latitude', 11, 8)->change();
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
        $tableName = $tablePrefix . 'requests';

        Schema::disableForeignKeyConstraints();

        Schema::table($tableName, function (Blueprint $table) {
            $table->decimal('ip_latitude', 10, 8)->change();
            $table->decimal('ip_longitude', 10, 8)->change();
        });

        Schema::enableForeignKeyConstraints();
    }
}
