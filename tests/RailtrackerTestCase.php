<?php

namespace Railroad\Railtracker\Tests;

use Carbon\Carbon;
use Doctrine\ORM\EntityManager;
use Exception;
use Faker\Generator;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Router;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Railroad\Railtracker\Console\Commands\ProcessTrackings;
use Railroad\Railtracker\Loggers\RailtrackerQueryLogger;
use Railroad\Railtracker\Middleware\RailtrackerMiddleware;
use Railroad\Railtracker\Providers\RailtrackerServiceProvider;
use Railroad\Railtracker\Services\BatchService;
use Railroad\Railtracker\Tests\Resources\Exceptions\Handler;
use Railroad\Railtracker\Tests\Resources\Models\User;

class RailtrackerTestCase extends BaseTestCase
{
    /**
     * @var Generator
     */
    protected $faker;

    /**
     * @var DatabaseManager
     */
    protected $databaseManager;

    /**
     * @var AuthManager
     */
    protected $authManager;

    /**
     * @var Router
     */
    protected $router;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /** @var array */
    protected $firedEvents;

    /** @var BatchService */
    protected $batchService;

    /** @var RailtrackerQueryLogger */
    protected $queryLogger;

    /** @var RailtrackerMiddleware $railtrackerMiddleware */
    protected $railtrackerMiddleware;

    public static $prefixForTestBatchKeyPrefix = 'railtrackerTest-';

    const USER_AGENT_CHROME_WINDOWS_10 = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36';

    protected function setUp(): void
    {
        parent::setUp();

        $this->getEnvironmentSetUp($this->app);

        if (!defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }

        $this->artisan('migrate');
        $this->artisan('cache:clear', []);

        $this->faker = $this->app->make(Generator::class);
        $this->databaseManager = $this->app->make(DatabaseManager::class);
        $this->authManager = $this->app->make(AuthManager::class);
        $this->router = $this->app->make(Router::class);
        $this->batchService = $this->app->make(BatchService::class);
        $this->railtrackerMiddleware = $this->app->make(RailtrackerMiddleware::class);

        // clear everything *first*
        $toDelete =
            $this->batchService->connection()
                ->keys('*');
        if (!empty($toDelete)) {
            $this->batchService->connection()
                ->del($toDelete);
        }

        // truncate all tables
        $tableNames = $this->databaseManager->connection()->getDoctrineSchemaManager()->listTableNames();

        $this->databaseManager->connection()->statement('SET FOREIGN_KEY_CHECKS=0;');

        foreach ($tableNames as $tableName) {
            if ($tableName != 'migrations') {
                $this->databaseManager->connection()->table($tableName)->truncate();
            }
        }

        $this->databaseManager->connection()->statement('SET FOREIGN_KEY_CHECKS=1;');

        // test time
        Carbon::setTestNow(Carbon::parse('2020-03-26 17:50:38.84123'));
    }

    public function tearDown(): void
    {
        $toDelete =
            $this->batchService->connection()
                ->keys('*');

        if (!empty($toDelete)) {
            $this->batchService->connection()
                ->del($toDelete);
        }

        parent::tearDown();
    }

    /**
     * Define environment setup. (This runs *before* "setUp" method above)
     *
     * @param \Illuminate\Foundation\Application $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // Setup package config for testing
        $defaultConfig = require(__DIR__ . '/../config/railtracker.php');

        config()->set('app.key', 'base64:191XeCIUtk74j6+7Qi4mpPoWjEPUNHurWYt/S08qH1k=');
        config()->set('session.driver', 'database');
        config()->set('session.connection', 'mysql-test');

        config()->set('railtracker.global_is_active', true);
        config()->set('railtracker.table_prefix', $defaultConfig['table_prefix']);
        config()->set('railtracker.cache_duration', 60);
        config()->set('railtracker.exclusion_regex_paths', $defaultConfig['exclusion_regex_paths']);

        config()->set('auth.providers.users.model', User::class);

        // db
        config()->set('railtracker.data_mode', 'host');
        config()->set('railtracker.database_connection_name', 'mysql-test');
        config()->set('database.default', 'mysql-test');
        config()->set(
            'database.connections.' . 'mysql-test',
            [
                'driver' => 'mysql',
                'host' => 'mysql',
                'port' => '3306',
                'database' => 'railtracker_test',
                'username' => 'root',
                'password' => 'root',
                'charset' => 'utf8',
                'collation' => 'utf8_unicode_ci',
                'prefix' => '',
                'strict' => true,
                'engine' => null,
            ]
        );

        // database
        config()->set('railtracker.redis_host', $defaultConfig['redis_host']);
        config()->set('railtracker.redis_port', $defaultConfig['redis_port']);
        config()->set('railtracker.development_mode', $defaultConfig['development_mode'] ?? true);
        config()->set('railtracker.database_driver', 'mysql');
        config()->set('railtracker.database_user', 'root');
        config()->set('railtracker.database_password', 'root');
        config()->set('railtracker.database_in_memory', false);
        config()->set('railtracker.enable_query_log', false);

        if (empty(config('railtracker.charset'))) {
            config()->set('railtracker.charset', 'utf8mb4');
        }
        if (empty(config('railtracker.collation'))) {
            config()->set('railtracker.collation', 'utf8mb4_unicode_ci');
        }

        // create test users table
        if (!$app['db']->connection()->getSchemaBuilder()->hasTable('users')) {
            $app['db']->connection()
                ->getSchemaBuilder()
                ->create(
                    'users',
                    function (Blueprint $table) {
                        $table->increments('id');
                        $table->string('email');
                        $table->string('remember_token')
                            ->nullable();
                    }
                );
        }

        // create session table
        if (!$app['db']->connection()->getSchemaBuilder()->hasTable('sessions')) {
            $app['db']->connection()
                ->getSchemaBuilder()
                ->create(
                    'sessions',
                    function (Blueprint $table) {
                        $table->string('id')
                            ->unique();
                        $table->integer('user_id')
                            ->nullable();
                        $table->string('ip_address', 45)
                            ->nullable();
                        $table->text('user_agent')
                            ->nullable();
                        $table->text('payload');
                        $table->integer('last_activity');
                    }
                );
        }

        $app['config']->set(
            'database.redis',
            [
                'client' => 'predis',
                'default' => [
                    'host' => env('REDIS_HOST', $defaultConfig['redis_host']),
                    //                    'password' => env('REDIS_PASSWORD', $defaultConfig['redis_port']),
                    'port' => env('REDIS_PORT', $defaultConfig['redis_port']),
                    'database' => 0,
                ]
            ]
        );

        $app['config']->set('railtracker.table_prefix', 'railtracker4_');
        $app['config']->set('railtracker.table_prefix_media_playback_tracking', 'railtracker4_');
        $app['config']->set('railtracker.media_playback_types_table', 'media_playback_types');
        $app['config']->set('railtracker.media_playback_sessions_table', 'media_playback_sessions');

        Carbon::setTestNow(Carbon::now());

        $time = Carbon::now()->timestamp . '_' . Carbon::now()->micro;

        $batchPrefix = 'railtracker_testing_' . $time . '_';

        $app['config']->set('railtracker.batch-prefix', $batchPrefix);

        config()->set('railtracker.ip_data_api_key', env('IP_DATA_API_KEY'));

        $app->register(RailtrackerServiceProvider::class);
    }

    /**
     * We don't want to use mockery so this is a reimplementation of the mockery version.
     *
     * @param array|string $events
     * @return $this
     */
    public function expectsEvents($events)
    {
        $events = is_array($events) ? $events : func_get_args();

        $mock =
            $this->getMockBuilder(Dispatcher::class)
                ->setMethods(['fire', 'dispatch'])
                ->getMockForAbstractClass();

        $mock->method('fire')
            ->willReturnCallback(
                function ($called) {
                    $this->firedEvents[] = $called;
                }
            );

        $mock->method('dispatch')
            ->willReturnCallback(
                function ($called) {
                    $this->firedEvents[] = $called;
                }
            );

        $this->app->instance('events', $mock);

        $this->beforeApplicationDestroyed(
            function () use ($events) {
                $fired = $this->getFiredEvents($events);
                if ($eventsNotFired = array_diff($events, $fired)) {
                    throw new Exception(
                        'These expected events were not fired: [' . implode(', ', $eventsNotFired) . ']'
                    );
                }
            }
        );

        return $this;
    }

    /**
     * @return int
     */
    public function createAndLogInNewUser()
    {
        $userId =
            $this->databaseManager->connection()
                ->query()
                ->from('users')
                ->insertGetId(
                    ['email' => $this->faker->email]
                );

        $this->authManager->guard()
            ->loginUsingId($userId);

        return $userId;
    }

    /**
     * @param string $statusCode
     * @return \Illuminate\Http\Response
     */
    public function createResponse($statusCode)
    {
        return response()->json([true], $statusCode);
    }

    /**
     * @param string $userAgent
     * @param string $url
     * @param string $referer
     * @param string $clientIp
     * @param string $method
     * @param array $cookies
     * @return Request
     */
    public function createRequest(
        $userAgent = self::USER_AGENT_CHROME_WINDOWS_10,
        $url = 'https://www.testing.com/?test=1',
        $referer = 'http://www.referer-testing.com/?test=2',
        $clientIp = '183.22.98.51',
        $method = 'GET',
        $cookies = []
    ) {
        return Request::create(
            $url,
            $method,
            [],
            $cookies,
            [],
            [
                'SCRIPT_NAME' => parse_url($url)['path'] ?? '',
                'REQUEST_URI' => parse_url($url)['path'] ?? '',
                'QUERY_STRING' => parse_url($url)['query'] ?? '',
                'REQUEST_METHOD' => 'GET',
                'SERVER_PROTOCOL' => 'HTTP/1.1',
                'GATEWAY_INTERFACE' => 'CGI/1.1',
                'REMOTE_PORT' => '62517',
                'SCRIPT_FILENAME' => '/var/www/index.php',
                'SERVER_ADMIN' => '[no address given]',
                'CONTEXT_DOCUMENT_ROOT' => '/var/www/',
                'CONTEXT_PREFIX' => '',
                'REQUEST_SCHEME' => 'http',
                'DOCUMENT_ROOT' => '/var/www/',
                'REMOTE_ADDR' => $clientIp,
                'HTTP_X_FORWARDED_FOR' => $clientIp,
                'SERVER_PORT' => '80',
                'SERVER_ADDR' => '172.21.0.7',
                'SERVER_NAME' => parse_url($url)['host'],
                'SERVER_SOFTWARE' => 'Apache/2.4.18 (Ubuntu)',
                'HTTP_ACCEPT_LANGUAGE' => 'en-GB,en-US;q=0.8,en;q=0.6',
                'HTTP_ACCEPT_ENCODING' => 'gzip, deflate, sdch',
                'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'HTTP_USER_AGENT' => $userAgent,
                'HTTP_REFERER' => $referer,
                'HTTP_UPGRADE_INSECURE_REQUESTS' => '1',
                'HTTP_CONNECTION' => 'keep-alive',
                'HTTP_HOST' => parse_url($url)['host'],
                'FCGI_ROLE' => 'RESPONDER',
                'PHP_SELF' => '/index.php',
                'REQUEST_TIME_FLOAT' => 1496790020.5194,
                'REQUEST_TIME' => 1496790020,
                'argv' => ['test=1'],
            ]
        );
    }

    /**
     * @param null $clientIp
     * @return Request
     */
    public function randomRequest($clientIp = null)
    {
        $method = $this->faker->randomElement(['GET', 'POST']);

        $protocol = $this->faker->randomElement(['HTTP', 'HTTPS']);

        $domain = $this->faker->randomElement(
            [$this->faker->domainName, $this->faker->domainWord . '.' . $this->faker->domainName]
        );

        $path = $this->faker->randomElement(
            [
                '',
                '/',
                '/' . $this->faker->word,
                '/' . $this->faker->word . '/',
                '/' . $this->faker->word . '/' . rand() . '/' . $this->faker->password,
                '/' . implode('/', range(1000000, 1000100)),
            ]
        );

        $queryString = $this->faker->randomElement(
            [
                '',
                '?',
                '?' . $this->faker->word . '=' . rand(),
                '?' . $this->faker->word . '=' . rand() . '&' . $this->faker->word . '=' . rand(),
                '?' . implode('&' . rand() . '=', range(2000000, 2000100)),
            ]
        );

        if (!$clientIp) {
            $clientIp = $this->faker->ipv4;
        }

        if ($this->faker->boolean()) {
            $routeName = $this->faker->word . '.' . $this->faker->word . '.' . $this->faker->word;
            $routeAction = ucwords($this->faker->word) . ucwords($this->faker->word) . '@' . $this->faker->word;

            $route = $this->router->get(
                $path,
                [
                    'as' => $routeName,
                    'uses' => $routeAction,
                ]
            );
        }

        if ($this->faker->boolean()) {
            $userId = $this->createAndLogInNewUser();
        }

        $request = Request::create(
            $this->faker->url,
            $method,
            [],
            [],
            [],
            [
                'SCRIPT_NAME' => '/index.php',
                'REQUEST_URI' => $path . $queryString,
                'QUERY_STRING' => $queryString,
                'REQUEST_METHOD' => $method,
                'SERVER_PROTOCOL' => 'HTTP/1.1',
                'GATEWAY_INTERFACE' => 'CGI/1.1',
                'REMOTE_PORT' => '62517',
                'SCRIPT_FILENAME' => '/var/www/index.php',
                'SERVER_ADMIN' => '[no address given]',
                'CONTEXT_DOCUMENT_ROOT' => '/var/www/',
                'CONTEXT_PREFIX' => '',
                'REQUEST_SCHEME' => 'http',
                'DOCUMENT_ROOT' => '/var/www/',
                'REMOTE_ADDR' => $clientIp,
                'HTTP_X_FORWARDED_FOR' => $clientIp,
                'SERVER_PORT' => '80',
                'SERVER_ADDR' => '172.21.0.7',
                'SERVER_NAME' => $protocol,
                'SERVER_SOFTWARE' => 'Apache/2.4.18 (Ubuntu)',
                'HTTP_ACCEPT_LANGUAGE' => 'en-GB,en-US;q=0.8,en;q=0.6',
                'HTTP_ACCEPT_ENCODING' => 'gzip, deflate, sdch',
                'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'HTTP_USER_AGENT' => $this->faker->userAgent,
                'HTTP_REFERER' => $this->faker->url,
                'HTTP_UPGRADE_INSECURE_REQUESTS' => '1',
                'HTTP_CONNECTION' => 'keep-alive',
                'HTTP_HOST' => $domain,
                'FCGI_ROLE' => 'RESPONDER',
                'PHP_SELF' => '/index.php',
                'REQUEST_TIME_FLOAT' => 1496790020.5194,
                'REQUEST_TIME' => 1496790020,
                'argv' => ['test=1'],
            ]
        );

        if (isset($route)) {
            $request->setRouteResolver(
                function () use ($route) {
                    return $route;
                }
            );
        }

        if (isset($userId)) {
            $request->setUserResolver(
                function () use ($userId) {
                    return User::query()
                        ->find($userId);
                }
            );
        }

        return $request;
    }

    /**
     *
     */
    public function processTrackings()
    {
        try {
            $processTrackings = app()->make(ProcessTrackings::class);
            $processTrackings->handle();
        } catch (\Exception $exception) {
            error_log($exception);

            $this->fail(
                'RailtrackerTestCase::processTrackings threw exception with message: "' . $exception->getMessage() . '"'
            );
        }
    }

    /**
     * @param Request $request
     * @param null|Response|int|string $response
     */
    protected function sendRequest(Request $request, $response = 200)
    {
        if (gettype($response) === 'integer' || gettype($response) === 'string') {
            $response = $this->createResponse((integer)$response);
        }

        resolve(RailtrackerMiddleware::class)->handle(
            $request,
            function () use ($response) {
                return $response;
            }
        );
    }

    /**
     * @param $userId
     * @param int $limit
     * @param int $skip
     * @param string $orderByProperty
     * @param string $orderByDirection
     * @return mixed
     */
    protected function getRequestsForUser(
        $userId,
        $limit = 25,
        $skip = 0
    ) {
        $results = $this->databaseManager
            ->table(config('railtracker.table_prefix') . 'requests')
            ->where('user_id', $userId)
            ->limit($limit)
            ->skip($skip)
            ->get();

        return $results;
    }

    protected function seeDbWhileDebugging()
    {
        $tables =
            \Illuminate\Support\Facades\DB::connection()
                ->getDoctrineSchemaManager()
                ->listTableNames(); // stackoverflow.com/a/40632654

        foreach ($tables as $table) {
            $result =
                \Illuminate\Support\Facades\DB::connection()
                    ->table($table)
                    ->select('*')
                    ->get()
                    ->all();
            foreach ($result as &$r) { // this changes contents from "stdClass::__set_state(array(...))" to "array (...)"
                $r = json_decode(json_encode($r), true);
            }
            $results[$table] = $result;
        }

        return $results ?? [];
    }

    protected function handleRequest(Request $request)
    {
        $response = $this->createResponse(200);
        $next = function () use ($response) {
            return $response;
        };
        $this->railtrackerMiddleware->handle($request, $next);
    }

    protected function throwExceptionDuringRequest(
        Request $request,
        $responseStatus = 500,
        Exception $exception = null,
        $exceptionMessage = 'Exception from throwExceptionDuringRequest method of RailtrackerTestCase'
    ) {
        $response = $this->createResponse($responseStatus);

        if (!$exception) {
            //            $exception = new \Exception($exceptionMessage);
            $exception = new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException($exceptionMessage);
        }

        $next = function ($request) use ($response, $exception) {
            app(Handler::class)->render($request, $exception);

            return $response;
        };

        $this->railtrackerMiddleware->handle($request, $next);
    }
}