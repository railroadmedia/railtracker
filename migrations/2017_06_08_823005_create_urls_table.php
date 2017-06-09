<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUrlsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'railtracker_urls',
            function (Blueprint $table) {
                $table->bigIncrements('id');

                $table->bigInteger('protocol_id')->unsigned()->index();
                $table->bigInteger('domain_id')->unsigned()->index();
                $table->bigInteger('path_id')->unsigned()->nullable()->index();
                $table->bigInteger('query_id')->unsigned()->nullable()->index();

                $table->timestamp('created_at')->nullable()->index();
                $table->timestamp('updated_at')->nullable()->index();
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
        Schema::dropIfExists('railtracker_urls');
    }
}
