<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Railroad\Railtracker\Services\ConfigService;

class RenameMediaPlaybackSessionsTableSecondsWatched extends Migration
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
                $table->renameColumn('seconds_watched', 'seconds_played');
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
            ConfigService::$tableMediaPlaybackSessions,
            function (Blueprint $table) {
                $table->renameColumn('seconds_played', 'seconds_watched');
            }
        );
    }
}
