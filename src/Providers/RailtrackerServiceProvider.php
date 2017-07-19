<?php

namespace Railroad\Railtracker\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Railroad\Railtracker\Services\ConfigService;

class RailtrackerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        $this->setupConfig();

        $this->publishes(
            [
                __DIR__ . '/../../config/railtracker.php' => config_path('railtracker.php'),
            ]
        );

        $this->loadMigrationsFrom(__DIR__ . '/../../migrations');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {

    }

    private function setupConfig()
    {
        // Caching
        ConfigService::$cacheTime = config('railtracker.cache_duration');

        // Database
        ConfigService::$databaseConnectionName = config('railtracker.database_connection_name');

        // Tables
        ConfigService::$tableUrlProtocols = config('railtracker.tables.url_protocols');
        ConfigService::$tableUrlDomains = config('railtracker.tables.url_domains');
        ConfigService::$tableUrlPaths = config('railtracker.tables.url_paths');
        ConfigService::$tableUrlQueries = config('railtracker.tables.url_queries');
        ConfigService::$tableUrls = config('railtracker.tables.urls');
        ConfigService::$tableRoutes = config('railtracker.tables.routes');
        ConfigService::$tableRequestMethods = config('railtracker.tables.request_methods');
        ConfigService::$tableRequestAgents = config('railtracker.tables.request_agents');
        ConfigService::$tableRequestDevices = config('railtracker.tables.request_devices');
        ConfigService::$tableRequestLanguages = config('railtracker.tables.request_languages');
        ConfigService::$tableGeoIP = config('railtracker.tables.geoip');
        ConfigService::$tableRequests = config('railtracker.tables.requests');
        ConfigService::$tableResponses = config('railtracker.tables.responses');
        ConfigService::$tableResponseStatusCodes = config('railtracker.tables.response_status_codes');
        ConfigService::$tableExceptions = config('railtracker.tables.exceptions');
        ConfigService::$tableRequestExceptions = config('railtracker.tables.request_exceptions');
        ConfigService::$tableMediaPlaybackTypes = config('railtracker.tables.media_playback_types');
        ConfigService::$tableMediaPlaybackSessions = config('railtracker.tables.media_playback_sessions');

        //Excluded requests
        ConfigService::$requestExclusionPaths = config('railtracker.requestExclusionPaths');
    }
}