<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Railroad\Railtracker\Services\ConfigService;

class AddGeneratedHashColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(
            ConfigService::$tableRequestMethods,
            function (Blueprint $table) {
                $table->string('hash')
                    ->nullable()
                    ->unique();
            }
        );

        Schema::table(
            ConfigService::$tableRequestAgents,
            function (Blueprint $table) {
                $table->string('hash')
                    ->nullable()
                    ->unique();
            }
        );

        Schema::table(
            ConfigService::$tableRequestDevices,
            function (Blueprint $table) {
                $table->string('hash')
                    ->nullable()
                    ->unique();
            }
        );

        Schema::table(
            ConfigService::$tableRequestLanguages,
            function (Blueprint $table) {
                $table->string('hash')
                    ->nullable()
                    ->unique();
            }
        );

        Schema::table(
            ConfigService::$tableRoutes,
            function (Blueprint $table) {
                $table->string('hash')
                    ->nullable()
                    ->unique();
            }
        );

        Schema::table(
            ConfigService::$tableUrls,
            function (Blueprint $table) {
                $table->string('hash')
                    ->nullable()
                    ->unique();
            }
        );

        Schema::table(
            ConfigService::$tableUrlProtocols,
            function (Blueprint $table) {
                $table->string('hash')
                    ->nullable()
                    ->unique();
            }
        );

        Schema::table(
            ConfigService::$tableUrlDomains,
            function (Blueprint $table) {
                $table->string('hash')
                    ->nullable()
                    ->unique();
            }
        );

        Schema::table(
            ConfigService::$tableUrlPaths,
            function (Blueprint $table) {
                $table->string('hash')
                    ->nullable()
                    ->unique();
            }
        );

        Schema::table(
            ConfigService::$tableUrlQueries,
            function (Blueprint $table) {
                $table->string('hash')
                    ->nullable()
                    ->unique();
            }
        );

        Schema::table(
            ConfigService::$tableResponseStatusCodes,
            function (Blueprint $table) {
                $table->string('hash')
                    ->nullable()
                    ->unique();
            }
        );

        Schema::table(
            ConfigService::$tableExceptions,
            function (Blueprint $table) {
                $table->string('hash')
                    ->nullable()
                    ->unique();
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
            ConfigService::$tableRequestMethods,
            function (Blueprint $table) {
                $table->dropColumn('hash');
            }
        );
        Schema::table(
            ConfigService::$tableRequestAgents,
            function (Blueprint $table) {
                $table->dropColumn('hash');
            }
        );
        Schema::table(
            ConfigService::$tableRequestDevices,
            function (Blueprint $table) {
                $table->dropColumn('hash');
            }
        );
        Schema::table(
            ConfigService::$tableRequestLanguages,
            function (Blueprint $table) {
                $table->dropColumn('hash');
            }
        );
        Schema::table(
            ConfigService::$tableRoutes,
            function (Blueprint $table) {
                $table->dropColumn('hash');
            }
        );
        Schema::table(
            ConfigService::$tableUrls,
            function (Blueprint $table) {
                $table->dropColumn('hash');
            }
        );
        Schema::table(
            ConfigService::$tableUrlProtocols,
            function (Blueprint $table) {
                $table->dropColumn('hash');
            }
        );
        Schema::table(
            ConfigService::$tableUrlDomains,
            function (Blueprint $table) {
                $table->dropColumn('hash');
            }
        );
        Schema::table(
            ConfigService::$tableUrlPaths,
            function (Blueprint $table) {
                $table->dropColumn('hash');
            }
        );
        Schema::table(
            ConfigService::$tableUrlQueries,
            function (Blueprint $table) {
                $table->dropColumn('hash');
            }
        );
        Schema::table(
            ConfigService::$tableResponseStatusCodes,
            function (Blueprint $table) {
                $table->dropColumn('hash');
            }
        );
        Schema::table(
            ConfigService::$tableExceptions,
            function (Blueprint $table) {
                $table->dropColumn('hash');
            }
        );
    }
}
