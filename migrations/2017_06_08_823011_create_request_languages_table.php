<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Railroad\Railtracker\Services\ConfigService;

class CreateRequestLanguagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            ConfigService::$tableRequestLanguages,
            function (Blueprint $table) {
                $table->bigIncrements('id');

                $table->string('preference', 12)->index();
                $table->string('language_range', 180)->index();

                $table->unique(['preference', 'language_range']);
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
        Schema::dropIfExists(ConfigService::$tableRequestLanguages);
    }
}
