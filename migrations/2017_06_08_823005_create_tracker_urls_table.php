<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTrackerUrlsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'tracker_urls',
            function (Blueprint $table) {
                $table->bigIncrements('id');

                $table->bigInteger('protocol_id')->unsigned()->index();
                $table->bigInteger('domain_id')->unsigned()->index();
                $table->bigInteger('path_id')->unsigned()->nullable()->index();
                $table->bigInteger('query_id')->unsigned()->nullable()->index();

                $table->timestamp('created_at')->index();
                $table->timestamp('updated_at')->index();
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
        Schema::dropIfExists('tracker_urls');
    }
}
