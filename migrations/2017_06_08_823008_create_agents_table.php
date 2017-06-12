<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAgentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'railtracker_agents',
            function (Blueprint $table) {
                $table->bigIncrements('id');

                $table->string('name', 140)->unique();
                $table->string('browser', 64)->index();
                $table->string('browser_version', 32);

                $table->unique(['name', 'browser', 'browser_version']);

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
        Schema::dropIfExists('railtracker_agents');
    }
}
