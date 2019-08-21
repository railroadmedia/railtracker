<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
        Schema::create(
            config('railtracker.table_prefix') . 'requests',
            function (Blueprint $table) {

                $table->bigIncrements('id');

                $table->string('uuid', 64)->unique()->index();
                $table->string('cookie_id', 64)->nullable();

                $table->unsignedBigInteger('user_id')->unsigned()->nullable()->index();

                $table->string('url_protocol', 32);
                $table->string('url_domain', 128);
                $table->string('url_path', 512)->nullable();
                $table->string('url_query', 1280)->nullable();

                $table->string('method', 10)->nullable();

                $table->string('route_name', 840)->nullable();
                $table->string('route_action', 840)->nullable();

                $table->string('device_kind', 64)->nullable();
                $table->string('device_model', 64)->nullable();
                $table->string('device_platform', 64)->nullable();
                $table->string('device_version', 64)->nullable();
                $table->boolean('device_is_mobile');

                $table->string('agent_string', 560)->nullable();
                $table->string('agent_browser', 64)->nullable();
                $table->string('agent_browser_version', 64)->nullable();

                $table->string('referer_url_protocol', 32);
                $table->string('referer_url_domain', 128);
                $table->string('referer_url_path', 512)->nullable();
                $table->string('referer_url_query', 1280)->nullable();

                $table->string('language_preference', 10)->nullable();
                $table->string('language_range', 64)->nullable();

                $table->string('ip_address', 128)->nullable();
                $table->decimal('ip_latitude', 10, 8)->nullable();
                $table->decimal('ip_longitude', 10, 8)->nullable();
                $table->string('ip_country_code', 6)->nullable();
                $table->string('ip_country_name', 128)->nullable();
                $table->string('ip_region', 128)->nullable();
                $table->string('ip_city', 128)->nullable();
                $table->string('ip_postal_zip_code', 16)->nullable();
                $table->string('ip_timezone', 64)->nullable();
                $table->string('ip_currency', 16)->nullable();

                $table->boolean('is_robot')->index();

                $table->unsignedInteger('response_status_code', false, true)->nullable();
                $table->unsignedBigInteger('response_duration_ms', false, true)->nullable();

                /*
                 * RE size, see
                 * https://stackoverflow.com/questions/13506832/what-is-the-mysql-varchar-max-size
                 * https://stackoverflow.com/questions/4420164/how-much-utf-8-text-fits-in-a-mysql-text-field
                 *
                 * current row size?
                 * max of all strings as of Aug 2019 is 9914 characters which is maybe 29742
                 * 64+64+32+128+512+1280+10+840+840+64+64+64+64+560+64+64+32+128+512+1280+10+64+128+6+128+128+128+16+64+16+1280+1280
                 *
                 * + whatever these would be:
                 *      * bigIncrements('id')
                 *      * unsignedBigInteger('user_id')->unsigned()->nullable()->index()
                 *      * boolean('device_is_mobile')
                 *      * decimal('ip_latitude', 10, 8)->nullable()
                 *      * decimal('ip_longitude', 10, 8)->nullable()
                 *      * boolean('is_robot')->index()
                 *      * unsignedInteger('response_status_code', false, true)->nullable()
                 *      * unsignedBigInteger('response_duration_ms', false, true)->nullable()
                 *      * unsignedInteger('exception_code', false, true)->nullable()
                 *      * unsignedInteger('exception_line', false, true)->nullable()
                 *      * dateTime('requested_on', 5)->index()
                 *      * dateTime('responded_on', 5)->index()->nullable()
                 */

                $table->unsignedInteger('exception_code', false, true)->nullable();
                $table->unsignedInteger('exception_line', false, true)->nullable();
                $table->string('exception_class', 1024)->nullable();
                $table->string('exception_file', 1024)->nullable();

//                $table->string('exception_message_id')->nullable();
//                $table->string('exception_trace_id')->nullable();

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
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(config('railtracker.table_prefix') . 'requests');
    }
}
