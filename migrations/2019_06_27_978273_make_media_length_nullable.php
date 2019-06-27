<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Railroad\Railtracker\Services\ConfigService;

class MakeMediaLengthNullable extends Migration
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
                $table->integer('media_length_seconds')->nullable()->change();
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
                $table->integer('media_length_seconds')->nullable(false)->change();
            }
        );
    }
}
