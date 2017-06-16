<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Railroad\Railtracker\Services\ConfigService;

class MediaPlaybackSessionsTableAddUuid extends Migration
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
                $table->string('uuid', 64)->unique()->nullable()->index();
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
                $table->removeColumn('uuid');
            }
        );
    }
}
