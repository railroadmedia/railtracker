<?php

namespace Railroad\Railtracker\Providers;

use Doctrine\Common\Cache\RedisCache;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Railroad\Railtracker\Console\Commands\FixDuplicates;
use Railroad\Railtracker\Console\Commands\EmptyLocalCache;
use Railroad\Railtracker\Console\Commands\PrintKeyCount;
use Railroad\Railtracker\Console\Commands\ProcessTrackings;
use Railroad\Railtracker\Console\Commands\ReHashExistingData;
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

        try{
            $this->setupConfig();
        }catch(\Exception $exception){
            error_log($exception);
        }

        $this->publishes(
            [
                __DIR__ . '/../../config/railtracker.php' => config_path('railtracker.php'),
            ]
        );

        $this->loadMigrationsFrom(__DIR__ . '/../../migrations');

        $this->loadRoutesFrom(__DIR__ . '/../Routes/railtracker_routes.php');
    }

    /**
     * @return void
     */
    private function setupConfig()
    {
        // setup redis
        $redis = new Redis();
        $redis->connect(
            config('railtracker.redis_host'),
            config('railtracker.redis_port')
        );
        $redisCache = new RedisCache();
        $redisCache->setRedis($redis);

        // redis cache instance is referenced in laravel container to be reused when needed
        app()->instance(RedisCache::class, $redisCache);

        $this->commands([
            ProcessTrackings::class,
            PrintKeyCount::class,
            EmptyLocalCache::class,
            ReHashExistingData::class,
            FixDuplicates::class,
        ]);
    }
}
