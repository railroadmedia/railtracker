<?php

use \Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class CreateTrackerSessionsTable extends Migration
{
    /**
     * Table related to this migration.
     *
     * @var string
     */
    private $table = 'tracker_sessions';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            $this->table,
            function ($table) {
                $table->bigIncrements('id');

                $table->string('uuid')->unique()->index();
                $table->bigInteger('user_id')->unsigned()->nullable()->index();
                $table->bigInteger('device_id')->unsigned()->nullable()->index();
                $table->bigInteger('agent_id')->unsigned()->nullable()->index();
                $table->string('client_ip')->index();
                $table->bigInteger('referer_id')->unsigned()->nullable()->index();
                $table->bigInteger('cookie_id')->unsigned()->nullable()->index();
                $table->bigInteger('geoip_id')->unsigned()->nullable()->index();
                $table->bigInteger('language_id')->unsigned()->nullable()->index();

                $table->foreign('language_id')
                    ->references('id')
                    ->on('tracker_languages')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');

                $table->boolean('is_robot');

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
        Schema::dropIfExists($this->table);
    }
}
