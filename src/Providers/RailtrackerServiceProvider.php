<?php

namespace Railroad\Railtracker\Providers;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Cache\PhpFileCache;
use Doctrine\Common\Cache\RedisCache;
use Doctrine\Common\EventManager;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain;
use Doctrine\DBAL\Logging\EchoSQLLogger;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Gedmo\DoctrineExtensions;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Railroad\Doctrine\TimestampableListener;
use Railroad\Doctrine\Types\Carbon\CarbonDateTimeTimezoneType;
use Railroad\Doctrine\Types\Carbon\CarbonDateTimeType;
use Railroad\Doctrine\Types\Carbon\CarbonDateType;
use Railroad\Doctrine\Types\Carbon\CarbonTimeType;
use Railroad\Doctrine\Types\Domain\GenderType;
use Railroad\Doctrine\Types\Domain\PhoneNumberType;
use Railroad\Doctrine\Types\Domain\TimezoneType;
use Railroad\Doctrine\Types\Domain\UrlType;
use Railroad\Railtracker\Console\Commands\RailtrackerTestingData;
use Railroad\Railtracker\Console\Commands\EmptyLocalCache;
use Railroad\Railtracker\Console\Commands\PrintKeyCount;
use Railroad\Railtracker\Console\Commands\ProcessTrackings;
use Railroad\Railtracker\Loggers\RailtrackerQueryLogger;
use Railroad\Railtracker\Managers\RailtrackerEntityManager;
use Railroad\Railtracker\Services\ConfigService;
use Railroad\Railtracker\Tests\Resources\RailtrackerQueryLogger;
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
            error_log($exception->getMessage());
            var_dump($exception->getMessage());
            die();
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
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
    }

    /**
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\ORM\ORMException
     */
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
        ConfigService::$exclusionRegexPaths = config('railtracker.exclusion_regex_paths');

        // doctrine
        Type::overrideType('datetime', CarbonDateTimeType::class);
        Type::overrideType('datetimetz', CarbonDateTimeTimezoneType::class);
        Type::overrideType('date', CarbonDateType::class);
        Type::overrideType('time', CarbonTimeType::class);

        !Type::hasType('url') ? Type::addType('url', UrlType::class) : null;
        !Type::hasType('phone_number') ? Type::addType('phone_number', PhoneNumberType::class) : null;
        !Type::hasType('timezone') ? Type::addType('timezone', TimezoneType::class) : null;
        !Type::hasType('gender') ? Type::addType('gender', GenderType::class) : null;

        // set proxy dir to temp folder on server
        $proxyDir = sys_get_temp_dir();

        // setup redis
        $redis = new Redis();
        $redis->connect(
            config('railtracker.redis_host'),
            config('railtracker.redis_port')
        );
        $redisCache = new RedisCache();
        $redisCache->setRedis($redis);

        $phpFileCache = new PhpFileCache($proxyDir);

        // redis cache instance is referenced in laravel container to be reused when needed
        app()->instance(RedisCache::class, $redisCache);

        AnnotationRegistry::registerLoader('class_exists');

        $annotationReader = new AnnotationReader();

        $cachedAnnotationReader = new CachedReader(
            $annotationReader, $phpFileCache
        );

        $driverChain = new MappingDriverChain();

        DoctrineExtensions::registerAbstractMappingIntoDriverChainORM(
            $driverChain,
            $cachedAnnotationReader
        );

        foreach (config('railtracker.entities') as $driverConfig) {
            $annotationDriver = new AnnotationDriver(
                $cachedAnnotationReader, $driverConfig['path']
            );

            $driverChain->addDriver(
                $annotationDriver,
                $driverConfig['namespace']
            );
        }

        // driver chain instance is referenced in laravel container to be reused when needed
        app()->instance(MappingDriverChain::class, $driverChain);

        $timestampableListener = new TimestampableListener();
        $timestampableListener->setAnnotationReader($cachedAnnotationReader);

        $eventManager = new EventManager();
        $eventManager->addEventSubscriber($timestampableListener);

        $ormConfiguration = new Configuration();
        $ormConfiguration->setMetadataCacheImpl($phpFileCache);
        $ormConfiguration->setQueryCacheImpl($redisCache);
        $ormConfiguration->setResultCacheImpl($redisCache);
        $ormConfiguration->setProxyDir($proxyDir);
        $ormConfiguration->setProxyNamespace('DoctrineProxies');
        $ormConfiguration->setAutoGenerateProxyClasses(
            config('railtracker.development_mode')
        );
        $ormConfiguration->setMetadataDriverImpl($driverChain);
        $ormConfiguration->setNamingStrategy(
            new UnderscoreNamingStrategy(CASE_LOWER)
        );

        // orm configuration instance is referenced in laravel container to be reused when needed
        app()->instance(Configuration::class, $ormConfiguration);

        if (config('railtracker.database_in_memory') !== true) {
            $databaseOptions = [
                'driver' => config('railtracker.database_driver'),
                'dbname' => config('railtracker.database_name'),
                'user' => config('railtracker.database_user'),
                'password' => config('railtracker.database_password'),
                'host' => config('railtracker.database_host'),
            ];
        } else {
            $databaseOptions = [
                'driver' => config('railtracker.database_driver'),
                'user' => config('railtracker.database_user'),
                'password' => config('railtracker.database_password'),
                'memory' => true,
            ];
        }

        // register the default entity manager
        $entityManager = RailtrackerEntityManager::create(
            $databaseOptions,
            $ormConfiguration,
            $eventManager
        );

        if (config('railtracker.enable_query_log', false) == true) {
            $logger = new RailtrackerQueryLogger();

            app()->instance(RailtrackerQueryLogger::class, $logger);

            $entityManager->getConnection()
                ->getConfiguration()
                ->setSQLLogger($logger);
        }

        $this->commands([
            ProcessTrackings::class,
            PrintKeyCount::class,
            RailtrackerTestingData::class,
            EmptyLocalCache::class
        ]);

        // register the entity manager as a singleton
        app()->instance(RailtrackerEntityManager::class, $entityManager);
    }
}