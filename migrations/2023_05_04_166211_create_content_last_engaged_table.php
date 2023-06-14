<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $tablePrefix = config('railtracker.table_prefix_media_playback_tracking');

        $contentLastEngagedTable = $tablePrefix .
            config('railtracker.content_last_engaged_table', 'content_last_engaged');

        if (!Schema::hasTable($contentLastEngagedTable)) {
            Schema::create(
                $contentLastEngagedTable,
                function (Blueprint $table) use($contentLastEngagedTable) {
                    $table->increments('id');
                    $table->integer('user_id')->index();
                    $table->integer('content_id')->index();
                    $table->integer('parent_content_id')->nullable()->index();
                    $table->integer('parent_playlist_id')->nullable()->index();

                    $table->timestamps();

                    $table->index(['user_id', 'parent_playlist_id'], 'upp');
                    $table->index(['user_id', 'parent_content_id'], 'upc');


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
        $tablePrefix = config('railtracker.table_prefix_media_playback_tracking');

        $contentLastEngagedTable = $tablePrefix .
            config('railtracker.content_last_engaged_table', 'content_last_engaged');

        Schema::dropIfExists($contentLastEngagedTable);
    }
};
