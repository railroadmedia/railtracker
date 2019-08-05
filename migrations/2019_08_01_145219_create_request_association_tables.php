<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRequestAssociationTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        // url
        Schema::create(
            config('railtracker.table_prefix') . 'url_protocols',
            function (Blueprint $table) {
                $table->string('url_protocol', 32)->unique();
            }
        );
        Schema::create(
            config('railtracker.table_prefix') . 'url_domains',
            function (Blueprint $table) {
                $table->string('url_domain', 128)->unique();
            }
        );
        Schema::create(
            config('railtracker.table_prefix') . 'url_paths',
            function (Blueprint $table) {
                $table->string('url_path', 512)->nullable()->unique();
            }
        );
        Schema::create(
            config('railtracker.table_prefix') . 'url_queries',
            function (Blueprint $table) {
                $table->string('url_query', 1280)->nullable()->unique();
            }
        );

        // method
        Schema::create(
            config('railtracker.table_prefix') . 'methods',
            function (Blueprint $table) {
                $table->string('method', 10)->nullable()->unique();
            }
        );

        // route
        Schema::create(
            config('railtracker.table_prefix') . 'route_names',
            function (Blueprint $table) {
                $table->string('route_name', 840)->nullable()->unique();
            }
        );
        Schema::create(
            config('railtracker.table_prefix') . 'route_actions',
            function (Blueprint $table) {
                $table->string('route_action', 840)->nullable()->unique();
            }
        );

        // device
        Schema::create(
            config('railtracker.table_prefix') . 'device_kinds',
            function (Blueprint $table) {
                $table->string('device_kind', 64)->unique();
            }
        );
        Schema::create(
            config('railtracker.table_prefix') . 'device_models',
            function (Blueprint $table) {
                $table->string('device_model', 64)->nullable()->unique();
            }
        );
        Schema::create(
            config('railtracker.table_prefix') . 'device_platforms',
            function (Blueprint $table) {
                $table->string('device_platform', 64)->nullable()->unique();
            }
        );
        Schema::create(
            config('railtracker.table_prefix') . 'device_versions',
            function (Blueprint $table) {
                $table->string('device_version', 64)->nullable()->unique();
            }
        );

        // agent
        Schema::create(
            config('railtracker.table_prefix') . 'agent_strings',
            function (Blueprint $table) {
                $table->string('agent_string', 560)->nullable()->unique();
            }
        );
        Schema::create(
            config('railtracker.table_prefix') . 'agent_browsers',
            function (Blueprint $table) {
                $table->string('agent_browser', 64)->nullable()->unique();
            }
        );
        Schema::create(
            config('railtracker.table_prefix') . 'agent_browser_versions',
            function (Blueprint $table) {
                $table->string('agent_browser_version', 64)->nullable()->unique();
            }
        );

        // language
        Schema::create(
            config('railtracker.table_prefix') . 'language_preferences',
            function (Blueprint $table) {
                $table->string('language_preference', 10)->nullable()->unique();
            }
        );
        Schema::create(
            config('railtracker.table_prefix') . 'language_ranges',
            function (Blueprint $table) {
                $table->string('language_range', 64)->nullable()->unique();
            }
        );

        // ip address
        Schema::create(
            config('railtracker.table_prefix') . 'ip_addresses',
            function (Blueprint $table) {
                $table->string('ip_address', 128)->nullable()->unique();
            }
        );
        Schema::create(
            config('railtracker.table_prefix') . 'ip_latitudes',
            function (Blueprint $table) {
                $table->decimal('ip_latitude', 10, 8)->nullable()->unique();
            }
        );
        Schema::create(
            config('railtracker.table_prefix') . 'ip_longitudes',
            function (Blueprint $table) {
                $table->decimal('ip_longitude', 10, 8)->nullable()->unique();
            }
        );
        Schema::create(
            config('railtracker.table_prefix') . 'ip_country_codes',
            function (Blueprint $table) {
                $table->string('ip_country_code', 6)->nullable()->unique();
            }
        );
        Schema::create(
            config('railtracker.table_prefix') . 'ip_country_names',
            function (Blueprint $table) {
                $table->string('ip_country_name', 128)->nullable()->unique();
            }
        );
        Schema::create(
            config('railtracker.table_prefix') . 'ip_regions',
            function (Blueprint $table) {
                $table->string('ip_region', 128)->nullable()->unique();
            }
        );
        Schema::create(
            config('railtracker.table_prefix') . 'ip_cities',
            function (Blueprint $table) {
                $table->string('ip_city', 128)->nullable()->unique();
            }
        );
        Schema::create(
            config('railtracker.table_prefix') . 'ip_postal_zip_codes',
            function (Blueprint $table) {
                $table->string('ip_postal_zip_code', 16)->nullable()->unique();
            }
        );
        Schema::create(
            config('railtracker.table_prefix') . 'ip_timezones',
            function (Blueprint $table) {
                $table->string('ip_timezone', 64)->nullable()->unique();
            }
        );
        Schema::create(
            config('railtracker.table_prefix') . 'ip_currencies',
            function (Blueprint $table) {
                $table->string('ip_currency', 16)->nullable()->unique();
            }
        );

        // response status code
        Schema::create(
            config('railtracker.table_prefix') . 'response_status_codes',
            function (Blueprint $table) {
                $table->unsignedInteger('response_status_code', false, true)->nullable()->unique();
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
        Schema::dropIfExists(config('railtracker.table_prefix') . 'url_protocols');
    }
}
