<?php

namespace Railroad\Railtracker\Tests;

use Carbon\Carbon;
use Exception;
use Faker\Generator;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Railroad\Railtracker\Providers\RailtrackerServiceProvider;
use Railroad\Railtracker\Tests\Resources\Models\User;

class TestCase extends BaseTestCase
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

    const USER_AGENT_CHROME_WINDOWS_10 = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36';

    protected function setUp()
    {
        parent::setUp();

        if (!defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }

        $this->artisan('migrate', []);
        $this->artisan('cache:clear', []);

        $this->faker = $this->app->make(Generator::class);
        $this->databaseManager = $this->app->make(DatabaseManager::class);
        $this->authManager = $this->app->make(AuthManager::class);
        $this->router = $this->app->make(Router::class);

        Carbon::setTestNow(Carbon::now());
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // Setup package config for testing
        $defaultConfig = require(__DIR__ . '/../config/railtracker.php');

        $app['config']->set('railtracker.global_is_active', true);
        $app['config']->set('railtracker.tables', $defaultConfig['tables']);
        $app['config']->set('railtracker.database_connection_name', 'testbench');
        $app['config']->set('railtracker.cache_duration', 60);

        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set(
            'database.connections.testbench',
            [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ]
        );
        $app['config']->set('auth.providers.users.model', User::class);

        $app['db']->connection()->getSchemaBuilder()->create(
            'users',
            function (Blueprint $table) {
                $table->increments('id');
                $table->string('email');
            }
        );

        $app->register(RailtrackerServiceProvider::class);
    }

    /**
     * We don't want to use mockery so this is a reimplementation of the mockery version.
     *
     * @param  array|string $events
     * @return $this
     *
     * @throws Exception
     */
    public function expectsEvents($events)
    {
        $events = is_array($events) ? $events : func_get_args();

        $mock = $this->getMockBuilder(Dispatcher::class)
            ->setMethods(['fire', 'dispatch'])
            ->getMockForAbstractClass();

        $mock->method('fire')->willReturnCallback(
            function ($called) {
                $this->firedEvents[] = $called;
            }
        );

        $mock->method('dispatch')->willReturnCallback(
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
                        'These expected events were not fired: [' .
                        implode(', ', $eventsNotFired) . ']'
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
        $userId = $this->databaseManager->connection()->query()->from('users')->insertGetId(
            ['email' => $this->faker->email]
        );

        $this->authManager->guard()->onceUsingId($userId);

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
     * @param $userAgent
     * @param string $url
     * @param string $referer
     * @param string $clientIp
     * @param string $method
     * @return Request
     */
    public function createRequest(
        $userAgent = self::USER_AGENT_CHROME_WINDOWS_10,
        $url = 'https://www.testing.com/?test=1',
        $referer = 'http://www.referer-testing.com/?test=2',
        $clientIp = '183.22.98.51',
        $method = 'GET'
    ) {
        return Request::create(
            $url,
            $method,
            [],
            [],
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
                'argv' =>
                    ['test=1']
            ]
        );
    }

    /**
     * @return Request
     */
    public function randomRequest()
    {
        $method = $this->faker->randomElement(['GET', 'POST']);

        $protocol = $this->faker->randomElement(['HTTP', 'HTTPS']);

        $domain =
            $this->faker->randomElement(
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

        $clientIp = $this->faker->randomElement([$this->faker->ipv4, $this->faker->ipv6]);

        if ($this->faker->boolean()) {
            $routeName = $this->faker->word . '.' . $this->faker->word . '.' . $this->faker->word;
            $routeAction =
                ucwords($this->faker->word) . ucwords($this->faker->word) . '@' . $this->faker->word;

            $route = $this->router->get(
                $path,
                [
                    'as' => $routeName,
                    'uses' => $routeAction
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
                'HTTP_REFERER' => $this->faker->randomElement([$this->faker->url, '']),
                'HTTP_UPGRADE_INSECURE_REQUESTS' => '1',
                'HTTP_CONNECTION' => 'keep-alive',
                'HTTP_HOST' => $domain,
                'FCGI_ROLE' => 'RESPONDER',
                'PHP_SELF' => '/index.php',
                'REQUEST_TIME_FLOAT' => 1496790020.5194,
                'REQUEST_TIME' => 1496790020,
                'argv' =>
                    ['test=1']
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
                    return User::query()->find($userId);
                }
            );
        }

        return $request;
    }
}