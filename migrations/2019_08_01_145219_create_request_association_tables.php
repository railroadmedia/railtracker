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
                $table->string('url_path', 191)->index();
                $table->string('url_query', 1280)->index()->nullable();

                $table->string('referer_url_protocol', 32)->index()->nullable();
                $table->string('referer_url_domain', 128)->index()->nullable();
                $table->string('referer_url_path', 191)->index()->nullable();
                $table->string('referer_url_query', 1280)->index()->nullable();

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

                // hash city

                $table->string('url_query_hash', 32)->index()->nullable();
                $table->string('referer_url_query_hash', 32)->index()->nullable();
                $table->string('route_name_hash', 32)->index()->nullable();
                $table->string('route_action_hash', 32)->index()->nullable();
                $table->string('agent_string_hash', 32)->index()->nullable();
                $table->string('exception_class_hash', 32)->index()->nullable();
                $table->string('exception_file_hash', 32)->index()->nullable();
                // todo: do for exception traces, create new table for them as well
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
                $table->string('url_path', 191)->unique('url_path_index');;
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

        // -------------------------------------------------------------------------------------------------------------
        // hash association tables -------------------------------------------------------------------------------------
        // -------------------------------------------------------------------------------------------------------------

        Schema::create(
            $tablePrefix . 'url_query_hash' . 'es',
            function (Blueprint $table){
                $table->string('url_query_hash', 32)->nullable()->unique('url_query_hash_index');
            }
        );

        // todo: remove? I don't think we need this
        Schema::create(
            $tablePrefix . 'referer_url_query_hash' . 'es',
            function (Blueprint $table){
                $table->string('referer_url_query_hash', 32)->nullable()->unique('referer_url_query_hash_index');
            }
        );
        Schema::create(
            $tablePrefix . 'route_name_hash' . 'es',
            function (Blueprint $table){
                $table->string('route_name_hash', 32)->nullable()->unique('route_name_hash_index');
            }
        );
        Schema::create(
            $tablePrefix . 'route_action_hash' . 'es',
            function (Blueprint $table){
                $table->string('route_action_hash', 32)->nullable()->unique('route_action_hash_index');
            }
        );
        Schema::create(
            $tablePrefix . 'agent_string_hash' . 'es',
            function (Blueprint $table){
                $table->string('agent_string_hash', 32)->nullable()->unique('agent_string_hash_index');
            }
        );
        Schema::create(
            $tablePrefix . 'exception_class_hash' . 'es',
            function (Blueprint $table){
                $table->string('exception_class_hash', 32)->nullable()->unique('exception_class_hash_index');
            }
        );
        Schema::create(
            $tablePrefix . 'exception_file_hash' . 'es',
            function (Blueprint $table){
                $table->string('exception_file_hash', 32)->nullable()->unique('exception_file_hash_index');
            }
        );

        // -------------------------------------------------------------------------------------------------------------
        // Adding Foreign Keys -----------------------------------------------------------------------------------------
        // -------------------------------------------------------------------------------------------------------------

        Schema::table($tablePrefix . 'requests', function (Blueprint $table) use ($tablePrefix){$table
            ->foreign('url_protocol')
            ->references('url_protocol')
            ->on($tablePrefix . 'url_protocols');});

        Schema::table($tablePrefix . 'requests', function (Blueprint $table) use ($tablePrefix){$table
            ->foreign('referer_url_protocol')
            ->references('url_protocol')
            ->on($tablePrefix . 'url_protocols');});

        Schema::table($tablePrefix . 'requests', function (Blueprint $table) use ($tablePrefix){$table
            ->foreign('url_domain')
            ->references('url_domain')
            ->on($tablePrefix . 'url_domains');});

        Schema::table($tablePrefix . 'requests', function (Blueprint $table) use ($tablePrefix){$table
            ->foreign('referer_url_domain')
            ->references('url_domain')
            ->on($tablePrefix . 'url_domains');});

        Schema::table($tablePrefix . 'requests', function (Blueprint $table) use ($tablePrefix){$table
            ->foreign('url_path')
            ->references('url_path')
            ->on($tablePrefix . 'url_paths');});

        Schema::table($tablePrefix . 'requests', function (Blueprint $table) use ($tablePrefix){$table
            ->foreign('referer_url_path')
            ->references('url_path')
            ->on($tablePrefix . 'url_paths');});

//        Schema::table($tablePrefix . 'requests', function (Blueprint $table) use ($tablePrefix){$table
//            ->foreign('url_query')
//            ->references('url_query')
//            ->on($tablePrefix . 'url_queries');});

//        Schema::table($tablePrefix . 'requests', function (Blueprint $table) use ($tablePrefix){$table
//            ->foreign('referer_url_query')
//            ->references('url_query')
//            ->on($tablePrefix . 'url_queries');});

        Schema::table($tablePrefix . 'requests', function (Blueprint $table) use ($tablePrefix){$table
            ->foreign('method')
            ->references('method')
            ->on($tablePrefix . 'methods');});

//        Schema::table($tablePrefix . 'requests', function (Blueprint $table) use ($tablePrefix){$table
//            ->foreign('route_name')
//            ->references('route_name')
//            ->on($tablePrefix . 'route_names');});

//        Schema::table($tablePrefix . 'requests', function (Blueprint $table) use ($tablePrefix){$table
//            ->foreign('route_action')
//            ->references('route_action')
//            ->on($tablePrefix . 'route_actions');});

        Schema::table($tablePrefix . 'requests', function (Blueprint $table) use ($tablePrefix){$table
            ->foreign('device_kind')
            ->references('device_kind')
            ->on($tablePrefix . 'device_kinds');});

        Schema::table($tablePrefix . 'requests', function (Blueprint $table) use ($tablePrefix){$table
            ->foreign('device_model')
            ->references('device_model')
            ->on($tablePrefix . 'device_models');});

        Schema::table($tablePrefix . 'requests', function (Blueprint $table) use ($tablePrefix){$table
            ->foreign('device_platform')
            ->references('device_platform')
            ->on($tablePrefix . 'device_platforms');});

        Schema::table($tablePrefix . 'requests', function (Blueprint $table) use ($tablePrefix){$table
            ->foreign('device_version')
            ->references('device_version')
            ->on($tablePrefix . 'device_versions');});

//        Schema::table($tablePrefix . 'requests', function (Blueprint $table) use ($tablePrefix){$table
//            ->foreign('agent_string')
//            ->references('agent_string')
//            ->on($tablePrefix . 'agent_strings');});

        Schema::table($tablePrefix . 'requests', function (Blueprint $table) use ($tablePrefix){$table
            ->foreign('agent_browser')
            ->references('agent_browser')
            ->on($tablePrefix . 'agent_browsers');});

        Schema::table($tablePrefix . 'requests', function (Blueprint $table) use ($tablePrefix){$table
            ->foreign('agent_browser_version')
            ->references('agent_browser_version')
            ->on($tablePrefix . 'agent_browser_versions');});

        Schema::table($tablePrefix . 'requests', function (Blueprint $table) use ($tablePrefix){$table
            ->foreign('language_preference')
            ->references('language_preference')
            ->on($tablePrefix . 'language_preferences');});

        Schema::table($tablePrefix . 'requests', function (Blueprint $table) use ($tablePrefix){$table
            ->foreign('language_range')
            ->references('language_range')
            ->on($tablePrefix . 'language_ranges');});

        Schema::table($tablePrefix . 'requests', function (Blueprint $table) use ($tablePrefix){$table
            ->foreign('ip_address')
            ->references('ip_address')
            ->on($tablePrefix . 'ip_addresses');});

        Schema::table($tablePrefix . 'requests', function (Blueprint $table) use ($tablePrefix){$table
            ->foreign('ip_latitude')
            ->references('ip_latitude')
            ->on($tablePrefix . 'ip_latitudes');});

        Schema::table($tablePrefix . 'requests', function (Blueprint $table) use ($tablePrefix){$table
            ->foreign('ip_longitude')
            ->references('ip_longitude')
            ->on($tablePrefix . 'ip_longitudes');});

        Schema::table($tablePrefix . 'requests', function (Blueprint $table) use ($tablePrefix){$table
            ->foreign('ip_country_code')
            ->references('ip_country_code')
            ->on($tablePrefix . 'ip_country_codes');});

        Schema::table($tablePrefix . 'requests', function (Blueprint $table) use ($tablePrefix){$table
            ->foreign('ip_country_name')
            ->references('ip_country_name')
            ->on($tablePrefix . 'ip_country_names');});

        Schema::table($tablePrefix . 'requests', function (Blueprint $table) use ($tablePrefix){$table
            ->foreign('ip_region')
            ->references('ip_region')
            ->on($tablePrefix . 'ip_regions');});

        Schema::table($tablePrefix . 'requests', function (Blueprint $table) use ($tablePrefix){$table
            ->foreign('ip_city')
            ->references('ip_city')
            ->on($tablePrefix . 'ip_cities');});

        Schema::table($tablePrefix . 'requests', function (Blueprint $table) use ($tablePrefix){$table
            ->foreign('ip_postal_zip_code')
            ->references('ip_postal_zip_code')
            ->on($tablePrefix . 'ip_postal_zip_codes');});

        Schema::table($tablePrefix . 'requests', function (Blueprint $table) use ($tablePrefix){$table
            ->foreign('ip_timezone')
            ->references('ip_timezone')
            ->on($tablePrefix . 'ip_timezones');});

        Schema::table($tablePrefix . 'requests', function (Blueprint $table) use ($tablePrefix){$table
            ->foreign('ip_currency')
            ->references('ip_currency')
            ->on($tablePrefix . 'ip_currencies');});

        Schema::table($tablePrefix . 'requests', function (Blueprint $table) use ($tablePrefix){$table
            ->foreign('response_status_code')
            ->references('response_status_code')
            ->on($tablePrefix . 'response_status_codes');});

        Schema::table($tablePrefix . 'requests', function (Blueprint $table) use ($tablePrefix){$table
            ->foreign('exception_code')
            ->references('exception_code')
            ->on($tablePrefix . 'exception_codes');});

        Schema::table($tablePrefix . 'requests', function (Blueprint $table) use ($tablePrefix){$table
            ->foreign('exception_line')
            ->references('exception_line')
            ->on($tablePrefix . 'exception_lines');});
        
//        Schema::table($tablePrefix . 'requests', function (Blueprint $table) use ($tablePrefix){$table
//            ->foreign('exception_class')
//            ->references('exception_class')
//            ->on($tablePrefix . 'exception_classes');});

//        Schema::table($tablePrefix . 'requests', function (Blueprint $table) use ($tablePrefix){$table
//            ->foreign('exception_file')
//            ->references('exception_file')
//            ->on($tablePrefix . 'exception_files');});

//        Schema::table($tablePrefix . 'requests', function (Blueprint $table) use ($tablePrefix){$table
//            ->foreign('exception_message')
//            ->references('exception_message')
//            ->on($tablePrefix . 'exception_messages');});

//        Schema::table($tablePrefix . 'requests', function (Blueprint $table) use ($tablePrefix){$table
//            ->foreign('exception_trace')
//            ->references('exception_trace')
//            ->on($tablePrefix . 'exception_traces');});

        Schema::table($tablePrefix . 'requests', function (Blueprint $table) use ($tablePrefix){$table
            ->foreign('url_query_hash')
            ->references('url_query_hash')
            ->on($tablePrefix . 'url_query_hashes');});

        Schema::table($tablePrefix . 'requests', function (Blueprint $table) use ($tablePrefix){$table
            ->foreign('referer_url_query_hash')
            ->references('referer_url_query_hash')
            ->on($tablePrefix . 'referer_url_query_hashes');});

        Schema::table($tablePrefix . 'requests', function (Blueprint $table) use ($tablePrefix){$table
            ->foreign('route_name_hash')
            ->references('route_name_hash')
            ->on($tablePrefix . 'route_name_hashes');});

        Schema::table($tablePrefix . 'requests', function (Blueprint $table) use ($tablePrefix){$table
            ->foreign('route_action_hash')
            ->references('route_action_hash')
            ->on($tablePrefix . 'route_action_hashes');});

        Schema::table($tablePrefix . 'requests', function (Blueprint $table) use ($tablePrefix){$table
            ->foreign('agent_string_hash')
            ->references('agent_string_hash')
            ->on($tablePrefix . 'agent_string_hashes');});

        Schema::table($tablePrefix . 'requests', function (Blueprint $table) use ($tablePrefix){$table
            ->foreign('exception_class_hash')
            ->references('exception_class_hash')
            ->on($tablePrefix . 'exception_class_hashes');});

        Schema::table($tablePrefix . 'requests', function (Blueprint $table) use ($tablePrefix){$table
            ->foreign('exception_file_hash')
            ->references('exception_file_hash')
            ->on($tablePrefix . 'exception_file_hashes');});
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
        Schema::dropIfExists($tablePrefix . 'url_query_hashes');
        Schema::dropIfExists($tablePrefix . 'referer_url_query_hashes');
        Schema::dropIfExists($tablePrefix . 'route_name_hashes');
        Schema::dropIfExists($tablePrefix . 'route_action_hashes');
        Schema::dropIfExists($tablePrefix . 'agent_string_hashes');
        Schema::dropIfExists($tablePrefix . 'exception_class_hashes');
        Schema::dropIfExists($tablePrefix . 'exception_file_hashes');
    }
}
