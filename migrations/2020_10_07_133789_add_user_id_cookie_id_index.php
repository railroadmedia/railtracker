<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUserIdCookieIdIndex extends Migration
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
                $table->index(['user_id', 'cookie_id'], 'railtracker4_requests_user_id_cookie_id_index');
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
                $table->dropIndex('railtracker4_requests_user_id_cookie_id_index');
            }
        );
    }
}
