<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Railroad\Railtracker\Services\ConfigService;

class FixWrongUniqueIndexes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(
            ConfigService::$tableRequestDevices,
            function (Blueprint $table) {
                $table->dropIndex('k_m_p_p');
                $table->unique(['kind', 'model', 'platform', 'platform_version', 'is_mobile'], 'k_m_p_p_i');
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
        Schema::table(
            ConfigService::$tableRequestDevices,
            function (Blueprint $table) {
                $table->dropIndex('k_m_p_p_i');
                $table->unique(['kind', 'model', 'platform', 'platform_version'], 'k_m_p_p');
            }
        );
    }
}
