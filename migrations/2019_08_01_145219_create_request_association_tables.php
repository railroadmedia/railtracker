<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        $tablePrefix = config('railtracker.table_prefix') ?? 'railtracker_';

        // url
        Schema::create(
            $tablePrefix . 'url_protocols',
            function (Blueprint $table) {
                $table->string('url_protocol', 32)->unique('url_protocol_indx');
            }
        );
        Schema::create(
            $tablePrefix . 'url_domains',
            function (Blueprint $table) {
                $table->string('url_domain', 128)->unique('url_domain_indx');
            }
        );
        Schema::create(
            $tablePrefix . 'url_paths',
            function (Blueprint $table) {
                $table->string('url_path', 512)->nullable();//->unique('url_path_indx');
            }
        );

        DB::statement('CREATE INDEX url_path_indx ON ' . $tablePrefix . 'url_paths' . ' (url_path(512));');
        //DB::statement('CREATE UNIQUE INDEX url_path_indx ON ' . $tablePrefix . 'url_paths' . ' (url_path(512));');
        //DB::statement('CREATE UNIQUE url_path_indx ON ' . $tablePrefix . 'url_paths' . ' (url_path(512));');


        Schema::create(
            $tablePrefix . 'url_queries',
            function (Blueprint $table) {
                $table->string('url_query', 1280)->nullable();//->unique('url_query_indx');
            }
        );

        DB::statement('CREATE INDEX url_query_indx ON ' . $tablePrefix . 'url_queries' . ' (url_query(1280));');
        //DB::statement('CREATE UNIQUE INDEX url_query_indx ON ' . $tablePrefix . 'url_queries' . ' (url_query(1280));');
        //DB::statement('CREATE UNIQUE url_query_indx ON ' . $tablePrefix . 'url_queries' . ' (url_query(1280));');


        // method
        Schema::create(
            $tablePrefix . 'methods',
            function (Blueprint $table) {
                $table->string('method', 10)->nullable()->unique('method_indx');
            }
        );


        // route
        Schema::create(
            $tablePrefix . 'route_names',
            function (Blueprint $table) {
                $table->string('route_name', 840)->nullable();//->unique('route_name_indx');
            }
        );

        DB::statement('CREATE INDEX route_name_indx ON ' . $tablePrefix . 'route_names' . ' (route_name(840));');


        Schema::create(
            $tablePrefix . 'route_actions',
            function (Blueprint $table) {
                $table->string('route_action', 840)->nullable();//->unique('route_action_indx');
            }
        );

        DB::statement('CREATE INDEX route_action_indx ON ' . $tablePrefix . 'route_actions' . ' (route_action(840));');


        // device
        Schema::create(
            $tablePrefix . 'device_kinds',
            function (Blueprint $table) {
                $table->string('device_kind', 64)->unique('device_kind_indx');
            }
        );
        Schema::create(
            $tablePrefix . 'device_models',
            function (Blueprint $table) {
                $table->string('device_model', 64)->nullable()->unique('device_model_indx');
            }
        );
        Schema::create(
            $tablePrefix . 'device_platforms',
            function (Blueprint $table) {
                $table->string('device_platform', 64)->nullable()->unique('device_platform_indx');
            }
        );
        Schema::create(
            $tablePrefix . 'device_versions',
            function (Blueprint $table) {
                $table->string('device_version', 64)->nullable()->unique('device_version_indx');
            }
        );

        // agent
        Schema::create(
            $tablePrefix . 'agent_strings',
            function (Blueprint $table) {
                $table->string('agent_string', 560)->nullable();//->unique('agent_string_indx');
            }
        );

        DB::statement('CREATE INDEX agent_string_indx ON ' . $tablePrefix . 'agent_strings' . ' (agent_string(560));');


        Schema::create(
            $tablePrefix . 'agent_browsers',
            function (Blueprint $table) {
                $table->string('agent_browser', 64)->nullable()->unique('agent_browser_indx');
            }
        );
        Schema::create(
            $tablePrefix . 'agent_browser_versions',
            function (Blueprint $table) {
                $table->string('agent_browser_version', 64)->nullable()->unique('agent_browser_version_indx');
            }
        );

        // language
        Schema::create(
            $tablePrefix . 'language_preferences',
            function (Blueprint $table) {
                $table->string('language_preference', 10)->nullable()->unique('language_preference_indx');
            }
        );
        Schema::create(
            $tablePrefix . 'language_ranges',
            function (Blueprint $table) {
                $table->string('language_range', 64)->nullable()->unique('language_range_indx');
            }
        );

        // ip address
        Schema::create(
            $tablePrefix . 'ip_addresses',
            function (Blueprint $table) {
                $table->string('ip_address', 128)->nullable()->unique('ip_address_indx');
            }
        );
        Schema::create(
            $tablePrefix . 'ip_latitudes',
            function (Blueprint $table) {
                $table->decimal('ip_latitude', 10, 8)->nullable()->unique('ip_latitude_indx');
            }
        );
        Schema::create(
            $tablePrefix . 'ip_longitudes',
            function (Blueprint $table) {
                $table->decimal('ip_longitude', 10, 8)->nullable()->unique('ip_longitude_indx');
            }
        );
        Schema::create(
            $tablePrefix . 'ip_country_codes',
            function (Blueprint $table) {
                $table->string('ip_country_code', 6)->nullable()->unique('ip_country_code_indx');
            }
        );
        Schema::create(
            $tablePrefix . 'ip_country_names',
            function (Blueprint $table) {
                $table->string('ip_country_name', 128)->nullable()->unique('ip_country_name_indx');
            }
        );
        Schema::create(
            $tablePrefix . 'ip_regions',
            function (Blueprint $table) {
                $table->string('ip_region', 128)->nullable()->unique('ip_region_indx');
            }
        );
        Schema::create(
            $tablePrefix . 'ip_cities',
            function (Blueprint $table) {
                $table->string('ip_city', 128)->nullable()->unique('ip_city_indx');
            }
        );
        Schema::create(
            $tablePrefix . 'ip_postal_zip_codes',
            function (Blueprint $table) {
                $table->string('ip_postal_zip_code', 16)->nullable()->unique('ip_postal_zip_code_indx');
            }
        );
        Schema::create(
            $tablePrefix . 'ip_timezones',
            function (Blueprint $table) {
                $table->string('ip_timezone', 64)->nullable()->unique('ip_timezone_indx');
            }
        );
        Schema::create(
            $tablePrefix . 'ip_currencies',
            function (Blueprint $table) {
                $table->string('ip_currency', 16)->nullable()->unique('ip_currency_indx');
            }
        );

        // response status code
        Schema::create(
            $tablePrefix . 'response_status_codes',
            function (Blueprint $table) {
                $table->unsignedInteger('response_status_code', false, true)->nullable()->unique('response_status_code_indx');
            }
        );

        // exceptions
        Schema::create(
            $tablePrefix . 'exception_codes',
            function (Blueprint $table) {
                $table->unsignedInteger('exception_code', false, true)->nullable()->unique('exception_code_indx');
            }
        );

        Schema::create(
            $tablePrefix . 'exception_lines',
            function (Blueprint $table) {
                $table->unsignedInteger('exception_line', false, true)->nullable()->unique('exception_line_indx');
            }
        );

        Schema::create(
            $tablePrefix . 'exception_class',
            function (Blueprint $table) {
                $table->string('exception_class', 1280)->nullable();//->unique('exception_class_indx');
            }
        );

        DB::statement('CREATE INDEX exception_class_indx ON ' . $tablePrefix . 'exception_class' . ' (exception_class(1280));');


        Schema::create(
            $tablePrefix . 'exception_files',
            function (Blueprint $table) {
                $table->string('exception_file', 1280)->nullable();//->unique('exception_file_indx');
            }
        );

        DB::statement('CREATE INDEX exception_file_indx ON ' . $tablePrefix . 'exception_files' . ' (exception_file(1280));');


        Schema::create(
            $tablePrefix . 'exception_messages',
            function (Blueprint $table) {
                $table->string('exception_message', 65535)->nullable();//->unique('exception_message_indx');
            }
        );

        DB::statement('CREATE INDEX exception_message_indx ON ' . $tablePrefix . 'exception_messages' . ' (exception_message(65535));');


        Schema::create(
            $tablePrefix . 'exception_traces',
            function (Blueprint $table) {
                $table->string('exception_trace', 65535)->nullable();//->unique('exception_trace_indx');
            }
        );

        DB::statement('CREATE INDEX exception_trace_indx ON ' . $tablePrefix . 'exception_traces' . ' (exception_trace(65535));');
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $tablePrefix = config('railtracker.table_prefix') ?? 'railtracker_';

        Schema::dropIfExists($tablePrefix . 'url_protocols');
        Schema::dropIfExists($tablePrefix . 'url_domains');
        Schema::dropIfExists($tablePrefix . 'url_paths');
        Schema::dropIfExists($tablePrefix . 'url_queries');
        Schema::dropIfExists($tablePrefix . 'methods');
        Schema::dropIfExists($tablePrefix . 'route_names');
        Schema::dropIfExists($tablePrefix . 'route_actions');
        Schema::dropIfExists($tablePrefix . 'device_kinds');
        Schema::dropIfExists($tablePrefix . 'device_models');
        Schema::dropIfExists($tablePrefix . 'device_platforms');
        Schema::dropIfExists($tablePrefix . 'device_versions');
        Schema::dropIfExists($tablePrefix . 'agent_strings');
        Schema::dropIfExists($tablePrefix . 'agent_browsers');
        Schema::dropIfExists($tablePrefix . 'agent_browser_versions');
        Schema::dropIfExists($tablePrefix . 'language_preferences');
        Schema::dropIfExists($tablePrefix . 'language_ranges');
        Schema::dropIfExists($tablePrefix . 'ip_addresses');
        Schema::dropIfExists($tablePrefix . 'ip_latitudes');
        Schema::dropIfExists($tablePrefix . 'ip_longitudes');
        Schema::dropIfExists($tablePrefix . 'ip_country_codes');
        Schema::dropIfExists($tablePrefix . 'ip_country_names');
        Schema::dropIfExists($tablePrefix . 'ip_regions');
        Schema::dropIfExists($tablePrefix . 'ip_cities');
        Schema::dropIfExists($tablePrefix . 'ip_postal_zip_codes');
        Schema::dropIfExists($tablePrefix . 'ip_timezones');
        Schema::dropIfExists($tablePrefix . 'ip_currencies');
        Schema::dropIfExists($tablePrefix . 'response_status_codes');
        Schema::dropIfExists($tablePrefix . 'exception_codes');
        Schema::dropIfExists($tablePrefix . 'exception_lines');
        Schema::dropIfExists($tablePrefix . 'exception_class');
        Schema::dropIfExists($tablePrefix . 'exception_files');
        Schema::dropIfExists($tablePrefix . 'exception_messages');
        Schema::dropIfExists($tablePrefix . 'exception_traces');

        Schema::table($tablePrefix . 'url_paths', function($table) {
            $table->dropIndex('url_path_indx');
        });

        Schema::table($tablePrefix . 'url_queries', function($table) {
            $table->dropIndex('url_query_indx');
        });

        Schema::table($tablePrefix . 'route_names', function($table) {
            $table->dropIndex('route_name_indx');
        });

        Schema::table($tablePrefix . 'route_actions', function($table) {
            $table->dropIndex('route_action_indx');
        });

        Schema::table($tablePrefix . 'agent_strings', function($table) {
            $table->dropIndex('agent_string_indx');
        });

        Schema::table($tablePrefix . 'exception_class', function($table) {
            $table->dropIndex('exception_class_indx');
        });

        Schema::table($tablePrefix . 'exception_files', function($table) {
            $table->dropIndex('exception_file_indx');
        });

        Schema::table($tablePrefix . 'exception_messages', function($table) {
            $table->dropIndex('exception_message_indx');
        });

        Schema::table($tablePrefix . 'exception_traces', function($table) {
            $table->dropIndex('exception_trace_indx');
        });

    }
}
