<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Railroad\Railtracker\Services\ConfigService;

class AddIndexToUrlQueriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(
            ConfigService::$tableUrlQueries,
            function (Blueprint $table) {
                DB::statement(
                    'CREATE INDEX url_query_string_index ON ' .
                    ConfigService::$tableUrlQueries .
                    ' ("string(191)");'
                );
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
        Schema::table(ConfigService::$tableUrlQueries, function ($table) {
            $table->dropIndex([DB::raw('string(191)')]);
        });
    }
}
