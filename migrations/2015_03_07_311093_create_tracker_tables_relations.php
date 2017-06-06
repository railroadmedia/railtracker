<?php

use \Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class CreateTrackerTablesRelations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tracker_route_paths', function ($table) {
            $table->foreign('route_id')
                ->references('id')
                ->on('tracker_routes')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });

        Schema::table('tracker_route_path_parameters', function ($table) {
            $table->foreign('route_path_id')
                ->references('id')
                ->on('tracker_route_paths')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });

        Schema::table('tracker_referers', function ($table) {
            $table->foreign('domain_id')
                ->references('id')
                ->on('tracker_domains')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });

        Schema::table('tracker_sessions', function ($table) {
            $table->foreign('device_id')
                ->references('id')
                ->on('tracker_devices')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });

        Schema::table('tracker_sessions', function ($table) {
            $table->foreign('agent_id')
                ->references('id')
                ->on('tracker_agents')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });

        Schema::table('tracker_sessions', function ($table) {
            $table->foreign('referer_id')
                ->references('id')
                ->on('tracker_referers')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });

        Schema::table('tracker_sessions', function ($table) {
            $table->foreign('cookie_id')
                ->references('id')
                ->on('tracker_cookies')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });

        Schema::table('tracker_sessions', function ($table) {
            $table->foreign('geoip_id')
                ->references('id')
                ->on('tracker_geoip')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Tables will be dropped in the correct order... :)
    }
}
