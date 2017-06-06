<?php

use \Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class CreateTrackerCookiesTable extends Migration
{
    /**
     * Table related to this migration.
     *
     * @var string
     */
    private $table = 'tracker_cookies';

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

                $table->string('uuid')->unique();

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
