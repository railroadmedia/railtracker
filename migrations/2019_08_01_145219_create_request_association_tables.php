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
                $table->string('url_protocol', 32)->unique('url_protocol_index');
            }
        );
        Schema::create(
            $tablePrefix . 'url_domains',
            function (Blueprint $table) {
                $table->string('url_domain', 128)->unique('url_domain_index');
            }
        );
        Schema::create(
            $tablePrefix . 'url_paths',
            function (Blueprint $table) {
                $table->string('url_path', 512)->nullable(); // note: unique index created below
            }
        );

        Schema::create(
            $tablePrefix . 'url_queries',
            function (Blueprint $table) {
                $table->string('url_query', 1280)->nullable(); // note: unique index created below
            }
        );

        // method
        Schema::create(
            $tablePrefix . 'methods',
            function (Blueprint $table) {
                $table->string('method', 10)->nullable()->unique('method_index');
            }
        );


        // route
        Schema::create(
            $tablePrefix . 'route_names',
            function (Blueprint $table) {
                $table->string('route_name', 840)->nullable(); // note: unique index created below
            }
        );

        Schema::create(
            $tablePrefix . 'route_actions',
            function (Blueprint $table) {
                $table->string('route_action', 840)->nullable(); // note: unique index created below
            }
        );


        // device
        Schema::create(
            $tablePrefix . 'device_kinds',
            function (Blueprint $table) {
                $table->string('device_kind', 64)->unique('device_kind_index');
            }
        );
        Schema::create(
            $tablePrefix . 'device_models',
            function (Blueprint $table) {
                $table->string('device_model', 64)->nullable()->unique('device_model_index');
            }
        );
        Schema::create(
            $tablePrefix . 'device_platforms',
            function (Blueprint $table) {
                $table->string('device_platform', 64)->nullable()->unique('device_platform_index');
            }
        );
        Schema::create(
            $tablePrefix . 'device_versions',
            function (Blueprint $table) {
                $table->string('device_version', 64)->nullable()->unique('device_version_index');
            }
        );

        // agent
        Schema::create(
            $tablePrefix . 'agent_strings',
            function (Blueprint $table) {
                $table->string('agent_string', 560)->nullable(); // note: unique index created below
            }
        );


        Schema::create(
            $tablePrefix . 'agent_browsers',
            function (Blueprint $table) {
                $table->string('agent_browser', 64)->nullable()->unique('agent_browser_index');
            }
        );
        Schema::create(
            $tablePrefix . 'agent_browser_versions',
            function (Blueprint $table) {
                $table->string('agent_browser_version', 64)->nullable()->unique('agent_browser_version_index');
            }
        );

        // language
        Schema::create(
            $tablePrefix . 'language_preferences',
            function (Blueprint $table) {
                $table->string('language_preference', 10)->nullable()->unique('language_preference_index');
            }
        );
        Schema::create(
            $tablePrefix . 'language_ranges',
            function (Blueprint $table) {
                $table->string('language_range', 64)->nullable()->unique('language_range_index');
            }
        );

        // ip address
        Schema::create(
            $tablePrefix . 'ip_addresses',
            function (Blueprint $table) {
                $table->string('ip_address', 128)->nullable()->unique('ip_address_index');
            }
        );
        Schema::create(
            $tablePrefix . 'ip_latitudes',
            function (Blueprint $table) {
                $table->decimal('ip_latitude', 10, 8)->nullable()->unique('ip_latitude_index');
            }
        );
        Schema::create(
            $tablePrefix . 'ip_longitudes',
            function (Blueprint $table) {
                $table->decimal('ip_longitude', 10, 8)->nullable()->unique('ip_longitude_index');
            }
        );
        Schema::create(
            $tablePrefix . 'ip_country_codes',
            function (Blueprint $table) {
                $table->string('ip_country_code', 6)->nullable()->unique('ip_country_code_index');
            }
        );
        Schema::create(
            $tablePrefix . 'ip_country_names',
            function (Blueprint $table) {
                $table->string('ip_country_name', 128)->nullable()->unique('ip_country_name_index');
            }
        );
        Schema::create(
            $tablePrefix . 'ip_regions',
            function (Blueprint $table) {
                $table->string('ip_region', 128)->nullable()->unique('ip_region_index');
            }
        );
        Schema::create(
            $tablePrefix . 'ip_cities',
            function (Blueprint $table) {
                $table->string('ip_city', 128)->nullable()->unique('ip_city_index');
            }
        );
        Schema::create(
            $tablePrefix . 'ip_postal_zip_codes',
            function (Blueprint $table) {
                $table->string('ip_postal_zip_code', 16)->nullable()->unique('ip_postal_zip_code_index');
            }
        );
        Schema::create(
            $tablePrefix . 'ip_timezones',
            function (Blueprint $table) {
                $table->string('ip_timezone', 64)->nullable()->unique('ip_timezone_index');
            }
        );
        Schema::create(
            $tablePrefix . 'ip_currencies',
            function (Blueprint $table) {
                $table->string('ip_currency', 16)->nullable()->unique('ip_currency_index');
            }
        );

        // response status code
        Schema::create(
            $tablePrefix . 'response_status_codes',
            function (Blueprint $table) {
                $table->unsignedInteger('response_status_code', false, true)->nullable()->unique('response_status_code_index');
            }
        );

        // exceptions
        Schema::create(
            $tablePrefix . 'exception_codes',
            function (Blueprint $table) {
                $table->unsignedInteger('exception_code', false, true)->nullable()->unique('exception_code_index');
            }
        );
        Schema::create(
            $tablePrefix . 'exception_lines',
            function (Blueprint $table) {
                $table->unsignedInteger('exception_line', false, true)->nullable()->unique('exception_line_index');
            }
        );
        Schema::create(
            $tablePrefix . 'exception_class',
            function (Blueprint $table) {
                $table->string('exception_class', 1280)->nullable(); // note: unique index created below
            }
        );
        Schema::create(
            $tablePrefix . 'exception_files',
            function (Blueprint $table) {
                $table->string('exception_file', 1280)->nullable(); // note: unique index created below
            }
        );
        Schema::create(
            $tablePrefix . 'exception_messages',
            function (Blueprint $table) {
                $table->string('exception_message', 65535)->nullable();
            }
        );
        Schema::create(
            $tablePrefix . 'exception_traces',
            function (Blueprint $table) {
                $table->string('exception_trace', 65535)->nullable();
            }
        );

        /*
         * A Note About Indexes For Long Text
         * ----------------------------------
         * Text columns longer than 191 characters with a unique key must have the unique key defined as below.
         *
         * They need a set key length that is within bounds (see stackoverflow.com/q/1814532). Ideally you could do
         * this by passing an integer as the second param of the `unique()` method, but that's not working (it's a
         * known and open issue: github.com/laravel/framework/issues/9293).
         *
         * I had issues passing with the syntax of the workaround in that discussion, and so did this instead:
         * stackoverflow.com/q/24792974.
         *
         * Jonathan, August 2019
         */

        DB::statement(
            'CREATE UNIQUE INDEX url_path_index ON ' .
            $tablePrefix . 'url_paths' . ' (url_path(191));'
        );
        DB::statement(
            'CREATE UNIQUE INDEX url_query_index ON ' .
            $tablePrefix . 'url_queries' . ' (url_query(191));'
        );
        DB::statement(
            'CREATE UNIQUE INDEX route_name_index ON ' .
            $tablePrefix . 'route_names' . ' (route_name(191));'
        );
        DB::statement(
            'CREATE UNIQUE INDEX route_action_index ON ' .
            $tablePrefix . 'route_actions' . ' (route_action(191));'
        );
        DB::statement(
            'CREATE UNIQUE INDEX agent_string_index ON ' .
            $tablePrefix . 'agent_strings' . ' (agent_string(191));'
        );
        DB::statement(
            'CREATE UNIQUE INDEX exception_class_index ON ' .
            $tablePrefix . 'exception_class' . ' (exception_class(191));'
        );
        DB::statement(
            'CREATE UNIQUE INDEX exception_file_index ON ' .
            $tablePrefix . 'exception_files' . ' (exception_file(191));'
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $tablePrefix = config('railtracker.table_prefix') ?? 'railtracker_';

        Schema::table($tablePrefix . 'url_paths', function($table) {
            $table->dropIndex('url_path_index');
        });

        Schema::table($tablePrefix . 'url_queries', function($table) {
            $table->dropIndex('url_query_index');
        });

        Schema::table($tablePrefix . 'route_names', function($table) {
            $table->dropIndex('route_name_index');
        });

        Schema::table($tablePrefix . 'route_actions', function($table) {
            $table->dropIndex('route_action_index');
        });

        Schema::table($tablePrefix . 'agent_strings', function($table) {
            $table->dropIndex('agent_string_index');
        });

        Schema::table($tablePrefix . 'exception_class', function($table) {
            $table->dropIndex('exception_class_index');
        });

        Schema::table($tablePrefix . 'exception_files', function($table) {
            $table->dropIndex('exception_file_index');
        });

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
    }
}
