<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLanguagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'railtracker_languages',
            function (Blueprint $table) {
                $table->bigIncrements('id');

                $table->string('preference', 12)->index();
                $table->string('language_range', 180)->index();

                $table->unique(['preference', 'language_range']);

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
        Schema::dropIfExists('railtracker_languages');
    }
}
