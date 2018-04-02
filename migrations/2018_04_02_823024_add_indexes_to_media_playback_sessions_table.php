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
                    ' (media_length_seconds);'
                );
                DB::statement(
                    'CREATE INDEX seconds_played_index ON ' .
                    ConfigService::$tableMediaPlaybackSessions .
                    ' (seconds_played);'
                );
                DB::statement(
                    'CREATE INDEX current_second_index ON ' .
                    ConfigService::$tableMediaPlaybackSessions .
                    ' (current_second);'
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
        DB::statement(
            'ALTER TABLE ' . ConfigService::$tableMediaPlaybackSessions .
            ' DROP INDEX media_length_seconds_index'
        );
        DB::statement(
            'ALTER TABLE ' . ConfigService::$tableMediaPlaybackSessions .
            ' DROP INDEX seconds_played_index'
        );
        DB::statement(
            'ALTER TABLE ' . ConfigService::$tableMediaPlaybackSessions .
            ' DROP INDEX current_second_index'
        );
    }
}
