<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
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

        DB::statement('CREATE INDEX exception_message_idx ON ' . $tableName . ' (exception_message(500));');
        DB::statement('CREATE INDEX exception_trace_idx ON ' . $tableName . ' (exception_trace(500));');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $tablePrefix = config('railtracker.table_prefix') ?? 'railtracker_';
        $tableName = $tablePrefix . 'requests';

        Schema::dropIfExists(config('railtracker.table_prefix') . 'requests');

        Schema::table($tableName, function($table) {
            $table->dropIndex('exception_message_idx');
        });
        Schema::table($tableName, function($table) {
            $table->dropIndex('exception_trace_idx');
        });
    }
}
