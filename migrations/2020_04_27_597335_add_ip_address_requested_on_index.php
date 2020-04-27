<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIpAddressRequestedOnIndex extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $tablePrefix = config('railtracker.table_prefix');
        $tableName = $tablePrefix . 'requests';

        Schema::table(
            $tableName,
            function (Blueprint $table) {
                $table->index(['ip_address', 'requested_on'], 'railtracker4_requests_ip_address_requested_on_index');
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
        $tableName = $tablePrefix . 'requests';

        Schema::table(
            $tableName,
            function (Blueprint $table) {
                $table->dropIndex('railtracker4_requests_ip_address_requested_on_index');
            }
        );
    }
}
