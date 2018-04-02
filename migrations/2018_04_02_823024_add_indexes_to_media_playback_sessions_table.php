<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Railroad\Railtracker\Services\ConfigService;

class AddIndexesToMediaPlaybackSessionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(
            ConfigService::$tableMediaPlaybackSessions,
            function (Blueprint $table) {
                DB::statement(
                    'CREATE INDEX media_length_seconds_index ON ' .
                    ConfigService::$tableMediaPlaybackSessions .
                    ' ("media_length_seconds(191)");'
                );
                DB::statement(
                    'CREATE INDEX seconds_played_index ON ' .
                    ConfigService::$tableMediaPlaybackSessions .
                    ' ("seconds_played(191)");'
                );
                DB::statement(
                    'CREATE INDEX current_second_index ON ' .
                    ConfigService::$tableMediaPlaybackSessions .
                    ' ("current_second(191)");'
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
        Schema::table(ConfigService::$tableMediaPlaybackSessions, function ($table) {
            $table->dropIndex([DB::raw('media_length_seconds(191)')]);
            $table->dropIndex([DB::raw('seconds_played(191)')]);
            $table->dropIndex([DB::raw('current_second(191)')]);
        });
    }
}
