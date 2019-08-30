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
        // requests tables =============================================================================================

        $tablePrefix = config('railtracker.table_prefix') ?? 'railtracker_';
        $tableName = $tablePrefix . 'requests';

        Schema::create(
            $tableName,
            function (Blueprint $table) {

                $table->bigIncrements('id');

                $table->string('uuid', 64)->unique()->index();
                $table->string('cookie_id', 64)->index()->nullable();

                $table->unsignedBigInteger('user_id')->unsigned()->index()->nullable();

                $table->string('url_protocol', 32)->index();
                $table->string('url_domain', 128)->index();
                $table->string('url_path', 512)->index()->nullable();
                $table->string('url_query', 1280)->index()->nullable();

                $table->string('method', 10)->index()->nullable();

                $table->string('route_name', 840)->index()->nullable();
                $table->string('route_action', 840)->index()->nullable();

                $table->string('device_kind', 64)->index()->nullable();
                $table->string('device_model', 64)->index()->nullable();
                $table->string('device_platform', 64)->index()->nullable();
                $table->string('device_version', 64)->index()->nullable();
                $table->boolean('device_is_mobile')->index();

                $table->string('agent_string', 560)->index()->nullable();
                $table->string('agent_browser', 64)->index()->nullable();
                $table->string('agent_browser_version', 64)->index()->nullable();

                $table->string('referer_url_protocol', 32)->index();
                $table->string('referer_url_domain', 128)->index();
                $table->string('referer_url_path', 512)->index()->nullable();
                $table->string('referer_url_query', 1280)->index()->nullable();

                $table->string('language_preference', 10)->index()->nullable();
                $table->string('language_range', 64)->index()->nullable();

                $table->string('ip_address', 128)->index()->nullable();
                $table->decimal('ip_latitude', 10, 8)->index()->nullable();
                $table->decimal('ip_longitude', 10, 8)->index()->nullable();
                $table->string('ip_country_code', 6)->index()->nullable();
                $table->string('ip_country_name', 128)->index()->nullable();
                $table->string('ip_region', 128)->index()->nullable();
                $table->string('ip_city', 128)->index()->nullable();
                $table->string('ip_postal_zip_code', 16)->index()->nullable();
                $table->string('ip_timezone', 64)->index()->nullable();
                $table->string('ip_currency', 16)->index()->nullable();

                $table->boolean('is_robot')->index();

                $table->unsignedInteger('response_status_code', false, true)->index()->nullable();
                $table->unsignedBigInteger('response_duration_ms', false, true)->index()->nullable();

                $table->unsignedInteger('exception_code', false, true)->index()->nullable();
                $table->unsignedInteger('exception_line', false, true)->index()->nullable();
                $table->string('exception_class', 1024)->index()->nullable();
                $table->string('exception_file', 1024)->index()->nullable();

                /*
                 * "32768" is just some arbitrary large value less than MySQL's row limit of 65535 value.
                 * Why "32768" exactly? Because it's a nice round number: 2ⁱ⁵ === 32768 && 8⁵ === 32768
                 */
                $table->string('exception_message', 32768)->nullable();
                $table->string('exception_trace', 32768)->nullable();

                $table->dateTime('requested_on', 5)->index();
                $table->dateTime('responded_on', 5)->index()->nullable();
            }
        );

        // associations tables =========================================================================================

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
                $table->unsignedInteger('response_status_code', false, true)->nullable()->unique(
                    'response_status_code_index'
                );
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
            $tablePrefix . 'exception_classes',
            function (Blueprint $table) {
                $table->string('exception_class', 1024)->nullable(); // note: unique index created below
            }
        );
        Schema::create(
            $tablePrefix . 'exception_files',
            function (Blueprint $table) {
                $table->string('exception_file', 1024)->nullable(); // note: unique index created below
            }
        );
        Schema::create(
            $tablePrefix . 'exception_messages',
            function (Blueprint $table) {
                $table->string('exception_message', 32768)->nullable();
            }
        );
        Schema::create(
            $tablePrefix . 'exception_traces',
            function (Blueprint $table) {
                $table->string('exception_trace', 32768)->nullable();
            }
        );



        // ----------------------------------------------------------------------------------------------------



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


        /* ------------------------------------------------------------------------------------------------------------------

        alter table `railtracker_v3_requests` add constraint `railtracker_v3_requests_url_query_foreign` foreign key (`url_query`) references `railtracker_v3_url_queries` (`url_query`))
        alter table `railtracker_v3_url_queries` add constraint `railtracker_v3_requests_url_query_foreign` foreign key (`url_query`) references `railtracker_v3_requests` (`url_query`))

        ------------------------------------------------------------------------------------------------------------------ */

# 1

```
DB::statement(
    'CREATE UNIQUE INDEX url_path_index ON ' .
    $tablePrefix . 'url_paths' . ' (url_path(191));'
);
```

# 2

```
DB::statement('ALTER TABLE ' . $tablePrefix . 'url_paths' . ' ADD UNIQUE url_path_index (url_path(191));');
```

# 3

```
DB::statement(
    'ALTER TABLE ' . $tablePrefix . 'url_paths ' .
    'ADD UNIQUE url_path_index (url_path(191)) ' .
    'CONSTRAINT railtracker_v3_requests_url_query_foreign FOREIGN KEY (url_path) ' .
    'REFERENCES railtracker_v3_requests (url_path))'
);
```

//        DB::statement(
//            'CREATE UNIQUE INDEX url_query_index ON ' .
//            $tablePrefix . 'url_queries' . ' (url_query(191));'
//        );

        //DB::statement('ALTER TABLE ' . $tablePrefix . 'url_queries' . ' ADD UNIQUE url_query_index (url_query(191));');
        DB::statement(
            'ALTER TABLE ' . $tablePrefix . 'url_queries ' .
            'ADD UNIQUE url_query_index (url_query(191)) ' .
            'CONSTRAINT railtracker_v3_requests_url_query_foreign FOREIGN KEY (url_query) ' .
            'REFERENCES railtracker_v3_requests (url_query))'
        );

//        DB::statement(
//            'CREATE UNIQUE INDEX route_name_index ON ' .
//            $tablePrefix . 'route_names' . ' (route_name(191));'
//        );

        //DB::statement('ALTER TABLE ' . $tablePrefix . 'route_names' . ' ADD UNIQUE route_name_index (route_name(191));');
        DB::statement(
            'ALTER TABLE ' . $tablePrefix . 'route_names ' .
            'ADD UNIQUE route_name_index (route_name(191)) ' .
            'CONSTRAINT railtracker_v3_requests_url_query_foreign FOREIGN KEY (route_name) ' .
            'REFERENCES railtracker_v3_requests (route_name))'
        );

//        DB::statement(
//            'CREATE UNIQUE INDEX route_action_index ON ' .
//            $tablePrefix . 'route_actions' . ' (route_action(191));'
//        );

        //DB::statement('ALTER TABLE ' . $tablePrefix . 'route_actions' . ' ADD UNIQUE route_action_index (route_action(191));');
        DB::statement(
            'ALTER TABLE ' . $tablePrefix . 'route_actions ' .
            'ADD UNIQUE route_action_index (route_action(191)) ' .
            'CONSTRAINT railtracker_v3_requests_url_query_foreign FOREIGN KEY (route_action) ' .
            'REFERENCES railtracker_v3_requests (route_action))'
        );

//        DB::statement(
//            'CREATE UNIQUE INDEX agent_string_index ON ' .
//            $tablePrefix . 'agent_strings' . ' (agent_string(191));'
//        );

        //DB::statement('ALTER TABLE ' . $tablePrefix . 'agent_strings' . ' ADD UNIQUE agent_string_index (agent_string(191));');
        DB::statement(
            'ALTER TABLE ' . $tablePrefix . 'agent_strings ' .
            'ADD UNIQUE agent_string_index (agent_string(191)) ' .
            'CONSTRAINT railtracker_v3_requests_url_query_foreign FOREIGN KEY (agent_string) ' .
            'REFERENCES railtracker_v3_requests (agent_string))'
        );

//        DB::statement(
//            'CREATE UNIQUE INDEX exception_class_index ON ' .
//            $tablePrefix . 'exception_classes' . ' (exception_class(191));'
//        );

        //DB::statement('ALTER TABLE ' . $tablePrefix . 'exception_classes' . ' ADD UNIQUE exception_class_index (exception_class(191));');
        DB::statement(
            'ALTER TABLE ' . $tablePrefix . 'exception_classes ' .
            'ADD UNIQUE exception_class_index (exception_class(191)) ' .
            'CONSTRAINT railtracker_v3_requests_url_query_foreign FOREIGN KEY (exception_class) ' .
            'REFERENCES railtracker_v3_requests (exception_class))'
        );

//        DB::statement(
//            'CREATE UNIQUE INDEX exception_file_index ON ' .
//            $tablePrefix . 'exception_files' . ' (exception_file(191));'
//        );

        //DB::statement('ALTER TABLE ' . $tablePrefix . 'exception_files' . ' ADD UNIQUE exception_file_index (exception_file(191));');
        DB::statement(
            'ALTER TABLE ' . $tablePrefix . 'exception_files ' .
            'ADD UNIQUE exception_file_index (exception_file(191)) ' .
            'CONSTRAINT railtracker_v3_requests_url_query_foreign FOREIGN KEY (exception_file) ' .
            'REFERENCES railtracker_v3_requests (exception_file))'
        );


        // ----------------------------------------------------------------------------------------------------

//        /*
//         * Adding Foreign Keys
//         */
//
//        $tableConnections = [
//            [
//                'col' => 'url_protocol',
//                'foreignTable' => 'url_protocols',
//                'foreignColumn' => 'url_protocol',
//            ],
////            [
////                'foreignKey' => '',
////                'foreignTable' => '',
////                'foreignColumn' => '',
////            ],
//        ];
//
//        foreach($tableConnections as $tableConnection){
//            $tableName = $tablePrefix . 'requests';
//            $col = $tableConnection['col'];
//            $foreignTable = $tableConnection['foreignTable'];
//
//            Schema::table($tableName, function (Blueprint $table) use ($col, $foreignTable) {
//                $table->foreign($col)->references($col)->on($foreignTable);
//            });
//        }

//        $tableConnections = [
//            ['column' => 'url_protocol'],
//            ['column' => 'url_domain'],
//            ['column' => 'url_path'],
//            ['column' => 'url_query', 'association_table' => 'url_queries'],
//            ['column' => 'method'],
//            ['column' => 'route_name'],
//            ['column' => 'route_action'],
//            ['column' => 'device_kind'],
//            ['column' => 'device_model'],
//            ['column' => 'device_platform'],
//            ['column' => 'device_version'],
//            ['column' => 'agent_string'],
//            ['column' => 'agent_browser'],
//            ['column' => 'agent_browser_version'],
//            ['column' => 'language_preference'],
//            ['column' => 'language_range'],
//            ['column' => 'ip_address', 'association_table' => 'ip_addresses'],
//            ['column' => 'ip_latitude'],
//            ['column' => 'ip_longitude'],
//            ['column' => 'ip_country_code'],
//            ['column' => 'ip_country_name'],
//            ['column' => 'ip_region'],
//            ['column' => 'ip_city', 'association_table' => 'ip_cities'],
//            ['column' => 'ip_postal_zip_code'],
//            ['column' => 'ip_timezone'],
//            ['column' => 'ip_currency', 'association_table' => 'ip_currencies'],
//            ['column' => 'response_status_code'],
//            ['column' => 'exception_code'],
//            ['column' => 'exception_line'],
//            ['column' => 'exception_class', 'association_table' => 'exception_classes'],
//            ['column' => 'exception_file'],
//            ['column' => 'exception_message'],
//            ['column' => 'exception_trace'],
//        ];
//
//        $specialTables = [
//            'url_paths',
//            'url_queries',
//            'route_names',
//            'route_actions',
//            'agent_strings',
//            'exception_classes',
//            'exception_files',
//        ];
//
//        foreach ($tableConnections as $tableConnection) {
//            $column = $tableConnection['column'];
//            $associationTable = $tablePrefix . ($tableConnection['association_table'] ?? $column . 's');
//            $special = in_array($associationTable, $specialTables);
//            try {
//                Schema::table(
//                    $tablePrefix . 'requests',
//                    function (Blueprint $table) use ($column, $associationTable, $special) {
//                        // if ($special) {
//                        //     $table->foreign($column)->references($column(191))->on($associationTable);
//                        // } else {
//                            $table->foreign($column)->references($column)->on($associationTable);
//                        // }
//                        //$table->foreign($column)->references($column)->on($associationTable)->onUpdate('cascade');
//                    }
//                );
//            } catch (\Exception $exception) {
//                error_log('');
//                error_log('==========================================================================================');
//                error_log('------------------------------------------------------------------------------------------');
//                error_log('Failed to make fk-constraint for ' . $column);
//                error_log($exception);
//                error_log('------------------------------------------------------------------------------------------');
//                error_log('==========================================================================================');
//                error_log('');
//
//                dump('Failed to make fk-constraint for ' . $column);
//            }
//        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // requests tables =============================================================================================

        $tablePrefix = config('railtracker.table_prefix') ?? 'railtracker_';

        Schema::dropIfExists($tablePrefix . 'requests');

        // associations tables =========================================================================================

        $tablePrefix = config('railtracker.table_prefix') ?? 'railtracker_';

        Schema::table(
            $tablePrefix . 'url_paths',
            function ($table) {
                $table->dropIndex('url_path_index');
            }
        );

        Schema::table(
            $tablePrefix . 'url_queries',
            function ($table) {
                $table->dropIndex('url_query_index');
            }
        );

        Schema::table(
            $tablePrefix . 'route_names',
            function ($table) {
                $table->dropIndex('route_name_index');
            }
        );

        Schema::table(
            $tablePrefix . 'route_actions',
            function ($table) {
                $table->dropIndex('route_action_index');
            }
        );

        Schema::table(
            $tablePrefix . 'agent_strings',
            function ($table) {
                $table->dropIndex('agent_string_index');
            }
        );

        Schema::table(
            $tablePrefix . 'exception_classes',
            function ($table) {
                $table->dropIndex('exception_class_index');
            }
        );

        Schema::table(
            $tablePrefix . 'exception_files',
            function ($table) {
                $table->dropIndex('exception_file_index');
            }
        );

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
        Schema::dropIfExists($tablePrefix . 'exception_classes');
        Schema::dropIfExists($tablePrefix . 'exception_files');
        Schema::dropIfExists($tablePrefix . 'exception_messages');
        Schema::dropIfExists($tablePrefix . 'exception_traces');
    }
}
