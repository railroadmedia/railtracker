<?php

namespace Railroad\Railtracker\Providers;

use Doctrine\Common\Cache\RedisCache;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\App;
use Railroad\Railtracker\Console\Commands\FixDuplicates;
use Railroad\Railtracker\Console\Commands\EmptyLocalCache;
use Railroad\Railtracker\Console\Commands\FixMissingIpData;
use Railroad\Railtracker\Console\Commands\LegacyMigrate;
use Railroad\Railtracker\Console\Commands\PrintKeyCount;
use Railroad\Railtracker\Console\Commands\ProcessTrackings;
use Railroad\Railtracker\Console\Commands\ReHashExistingData;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Doctrine\Common\Cache\Psr6\DoctrineProvider;
use Redis;

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

        try {
            $this->setupConfig();
        } catch (\Exception $exception) {
            error_log($exception);
        }

        $this->publishes(
            [
                __DIR__ . '/../../config/railtracker.php' => config_path('railtracker.php'),
            ]
        );

        //disable running migrations for tests in MWP since migrations are failing in sqlite.
        if (App::environment() != 'testing') {
            $this->loadMigrationsFrom(__DIR__ . '/../../migrations');
        }

        $this->loadRoutesFrom(__DIR__ . '/../Routes/railtracker_routes.php');
    }

    /**
     * @return void
     */
    private function setupConfig()
    {
        // array cache
        $arrayCacheAdapter = new ArrayAdapter();
        $doctrineArrayCache = DoctrineProvider::wrap($arrayCacheAdapter);
        app()->instance('RailtrackerArrayCache', $doctrineArrayCache);

        $this->commands([
            ProcessTrackings::class,
            PrintKeyCount::class,
            EmptyLocalCache::class,
            ReHashExistingData::class,
            FixDuplicates::class,
            FixMissingIpData::class,
            LegacyMigrate::class,
        ]);
    }
}
