<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMediaTrackingTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $tablePrefix = config('railtracker.table_prefix_media_playback_tracking') ?? 'railtracker3_';

        $mediaPlaybackTypesTable = $tablePrefix .
            config('railtracker.media_playback_types_table', 'media_playback_types');

        $mediaPlaybackSessionsTable = $tablePrefix .
            config('railtracker.media_playback_sessions_table', 'media_playback_sessions');

        if (!Schema::hasTable($mediaPlaybackTypesTable)) {
            Schema::create(
                $mediaPlaybackTypesTable,
                function (Blueprint $table) {
                    $table->bigIncrements('id');
                    $table->string('type', 128)->index();
                    $table->string('category', 128)->index();
                }
            );
        }

        if (!Schema::hasTable($mediaPlaybackSessionsTable)) {
            Schema::create(
                $mediaPlaybackSessionsTable,
                function (Blueprint $table) {
                    $table->bigIncrements('id');
                    $table->string('uuid', 64)->unique()->nullable()->index();
                    $table->string('media_id', 64)->index();
                    $table->integer('media_length_seconds')->nullable()->unsigned()->index();
                    $table->bigInteger('user_id')->unsigned()->nullable()->index();
                    $table->bigInteger('type_id')->unsigned()->index();
                    $table->bigInteger('seconds_played')->unsigned()->index();
                    $table->bigInteger('current_second')->unsigned()->index();
                    $table->dateTime('started_on')->index();
                    $table->dateTime('last_updated_on')->index();
                }
            );
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $tablePrefix = config('railtracker.table_prefix_media_playback_tracking') ?? 'railtracker_';

        $mediaPlaybackTypesTable = $tablePrefix . config('railtracker.media_playback_types_table');
        $mediaPlaybackSessionsTable = $tablePrefix . config('railtracker.media_playback_sessions_table');

        Schema::dropIfExists($mediaPlaybackTypesTable);
        Schema::dropIfExists($mediaPlaybackSessionsTable);
    }
}
