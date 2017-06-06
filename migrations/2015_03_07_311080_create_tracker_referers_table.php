<?php

use \Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class CreateTrackerReferersTable extends Migration
{
    /**
     * Table related to this migration.
     *
     * @var string
     */
    private $table = 'tracker_referers';

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

                $table->bigInteger('domain_id')->unsigned()->index();
                $table->string('url')->index();
                $table->string('host');

                $table->string('medium')->nullable()->index();
                $table->string('source')->nullable()->index();
                $table->string('search_terms_hash')->nullable()->index();

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
