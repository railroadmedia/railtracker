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

    const USER_AGENT_CHROME_WINDOWS_10 = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36';

    protected function setUp()
    {
        parent::setUp();

        $this->artisan('migrate', []);
        $this->artisan('cache:clear', []);

        $this->faker = $this->app->make(Generator::class);
        $this->databaseManager = $this->app->make(DatabaseManager::class);
        $this->authManager = $this->app->make(AuthManager::class);

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
     * @param $userAgent
     * @param string $url
     * @param string $method
     * @return Request
     */
    public function createRequest(
        $userAgent,
        $url = 'https://www.testing.com/?test=1',
        $method = 'GET'
    ) {
        return Request::create(
            $url,
            $method,
            [],
            [],
            [],
            [
                'SCRIPT_NAME' => '/index.php',
                'REQUEST_URI' => '/',
                'QUERY_STRING' => '',
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
                'REMOTE_ADDR' => '10.0.75.1',
                'SERVER_PORT' => '80',
                'SERVER_ADDR' => '172.21.0.7',
                'SERVER_NAME' => 'dev.drumeo.com',
                'SERVER_SOFTWARE' => 'Apache/2.4.18 (Ubuntu)',
                'HTTP_ACCEPT_LANGUAGE' => 'en-GB,en-US;q=0.8,en;q=0.6',
                'HTTP_ACCEPT_ENCODING' => 'gzip, deflate, sdch',
                'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'HTTP_USER_AGENT' => $userAgent,
                'HTTP_UPGRADE_INSECURE_REQUESTS' => '1',
                'HTTP_CONNECTION' => 'keep-alive',
                'HTTP_HOST' => 'dev.drumeo.com',
                'FCGI_ROLE' => 'RESPONDER',
                'PHP_SELF' => '/index.php',
                'REQUEST_TIME_FLOAT' => 1496790020.5194,
                'REQUEST_TIME' => 1496790020,
                'argv' =>
                    ['test=1']
            ]
        );
    }
}