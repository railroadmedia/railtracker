<?php

namespace Railroad\Railtracker\Tests\Integration;

use Carbon\Carbon;
use Railroad\Railtracker\Middleware\RailtrackerMiddleware;
use Railroad\Railtracker\Tests\Resources\Models\User;
use Railroad\Railtracker\Tests\TestCase;

class TrackerTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
    }

    public function test_track_protocol_http()
    {
        $url = 'http://test.com/';
        $request = $this->createRequest(TestCase::USER_AGENT_CHROME_WINDOWS_10, $url);

        $middleware = $this->app->make(RailtrackerMiddleware::class);

        $middleware->handle(
            $request,
            function () {
            }
        );

        $this->assertDatabaseHas(
            'tracker_protocols',
            [
                'protocol' => 'http',
            ]
        );
    }

    public function test_track_protocol_https()
    {
        $url = 'https://test.com/';
        $request = $this->createRequest(TestCase::USER_AGENT_CHROME_WINDOWS_10, $url);

        $middleware = $this->app->make(RailtrackerMiddleware::class);

        $middleware->handle(
            $request,
            function () {
            }
        );

        $this->assertDatabaseHas(
            'tracker_protocols',
            [
                'protocol' => 'https',
            ]
        );
    }

    public function test_track_domain()
    {
        $url = 'https://test.com/';
        $request = $this->createRequest(TestCase::USER_AGENT_CHROME_WINDOWS_10, $url);

        $middleware = $this->app->make(RailtrackerMiddleware::class);

        $middleware->handle(
            $request,
            function () {
            }
        );

        $this->assertDatabaseHas(
            'tracker_domains',
            [
                'name' => 'test.com',
            ]
        );
    }

    public function test_track_domain_sub()
    {
        $url = 'https://www.test.com/';
        $request = $this->createRequest(TestCase::USER_AGENT_CHROME_WINDOWS_10, $url);

        $middleware = $this->app->make(RailtrackerMiddleware::class);

        $middleware->handle(
            $request,
            function () {
            }
        );

        $this->assertDatabaseHas(
            'tracker_domains',
            [
                'name' => 'www.test.com',
            ]
        );
    }

    public function test_track_path()
    {
        $url = 'https://www.test.com/test-path/test/test2/file.php';
        $request = $this->createRequest(TestCase::USER_AGENT_CHROME_WINDOWS_10, $url);

        $middleware = $this->app->make(RailtrackerMiddleware::class);

        $middleware->handle(
            $request,
            function () {
            }
        );

        $this->assertDatabaseHas(
            'tracker_paths',
            [
                'path' => '/test-path/test/test2/file.php',
            ]
        );
    }

    public function test_track_path_no_file()
    {
        $url = 'https://www.test.com/test-path/test/test2';
        $request = $this->createRequest(TestCase::USER_AGENT_CHROME_WINDOWS_10, $url);

        $middleware = $this->app->make(RailtrackerMiddleware::class);

        $middleware->handle(
            $request,
            function () {
            }
        );

        $this->assertDatabaseHas(
            'tracker_paths',
            [
                'path' => '/test-path/test/test2',
            ]
        );
    }

    public function test_track_path_leading_slash_removed()
    {
        $url = 'https://www.test.com/test-path/test/test2/';
        $request = $this->createRequest(TestCase::USER_AGENT_CHROME_WINDOWS_10, $url);

        $middleware = $this->app->make(RailtrackerMiddleware::class);

        $middleware->handle(
            $request,
            function () {
            }
        );

        $this->assertDatabaseHas(
            'tracker_paths',
            [
                'path' => '/test-path/test/test2',
            ]
        );
    }

    public function test_track_query()
    {
        $url = 'https://www.test.com/test-path?test=1&test2=as7da98dsda3-23f23';
        $request = $this->createRequest(TestCase::USER_AGENT_CHROME_WINDOWS_10, $url);

        $middleware = $this->app->make(RailtrackerMiddleware::class);

        $middleware->handle(
            $request,
            function () {
            }
        );

        $this->assertDatabaseHas(
            'tracker_queries',
            [
                'string' => 'test=1&test2=as7da98dsda3-23f23',
            ]
        );
    }

    public function test_track_url()
    {
        $url = 'https://www.test.com/test-path/test/test2?test=1&test2=as7da98dsda3-23f23';
        $request = $this->createRequest(TestCase::USER_AGENT_CHROME_WINDOWS_10, $url);

        $middleware = $this->app->make(RailtrackerMiddleware::class);

        $middleware->handle(
            $request,
            function () {
            }
        );

        $this->assertDatabaseHas(
            'tracker_urls',
            [
                'protocol_id' => 1,
                'domain_id' => 1,
                'path_id' => 1,
                'query_id' => 1,
            ]
        );
    }

    public function test_track_url_no_query()
    {
        $url = 'https://www.test.com/test-path/test/test2';
        $request = $this->createRequest(TestCase::USER_AGENT_CHROME_WINDOWS_10, $url);

        $middleware = $this->app->make(RailtrackerMiddleware::class);

        $middleware->handle(
            $request,
            function () {
            }
        );

        $this->assertDatabaseHas(
            'tracker_urls',
            [
                'protocol_id' => 1,
                'domain_id' => 1,
                'path_id' => 1,
                'query_id' => null,
            ]
        );
    }

    public function test_track_url_no_path()
    {
        $url = 'https://www.test.com/';
        $request = $this->createRequest(TestCase::USER_AGENT_CHROME_WINDOWS_10, $url);

        $middleware = $this->app->make(RailtrackerMiddleware::class);

        $middleware->handle(
            $request,
            function () {
            }
        );

        $this->assertDatabaseHas(
            'tracker_urls',
            [
                'protocol_id' => 1,
                'domain_id' => 1,
                'path_id' => null,
                'query_id' => null,
            ]
        );
    }

    public function test_track_referer()
    {
        $url = 'https://www.test.com/';
        $refererUrl = 'https://www.referer.com/345/2?test=1';
        $request = $this->createRequest(TestCase::USER_AGENT_CHROME_WINDOWS_10, $url, $refererUrl);

        $middleware = $this->app->make(RailtrackerMiddleware::class);

        $middleware->handle(
            $request,
            function () {
            }
        );

        $this->assertDatabaseHas(
            'tracker_urls',
            [
                'protocol_id' => 1,
                'domain_id' => 2,
                'path_id' => 1,
                'query_id' => 1,
            ]
        );
    }

    public function test_track_route()
    {
        $path = '/test/path/1';
        $query = 'test1=2&test2=3';
        $routeName = 'test.route.name';
        $routeAction = 'TestController@test';

        $route = $this->router->get(
            $path,
            [
                'as' => $routeName,
                'uses' => $routeAction
            ]
        );

        $request =
            $this->createRequest(
                TestCase::USER_AGENT_CHROME_WINDOWS_10,
                'https://www.test.com' . $path . '$' . $query
            );

        $request->setRouteResolver(
            function () use ($route) {
                return $route;
            }
        );

        $middleware = $this->app->make(RailtrackerMiddleware::class);

        $middleware->handle(
            $request,
            function () {
            }
        );

        $this->assertDatabaseHas(
            'tracker_routes',
            [
                'name' => $routeName,
                'action' => $routeAction,
            ]
        );
    }

    public function test_track_route_non_existing()
    {
        $request =
            $this->createRequest(
                TestCase::USER_AGENT_CHROME_WINDOWS_10
            );

        $middleware = $this->app->make(RailtrackerMiddleware::class);

        $middleware->handle(
            $request,
            function () {
            }
        );

        $this->assertDatabaseMissing(
            'tracker_routes',
            [
                'id' => 1,
            ]
        );
    }

    public function test_agent_chrome_webkit()
    {
        $request = $this->createRequest(TestCase::USER_AGENT_CHROME_WINDOWS_10);

        $middleware = $this->app->make(RailtrackerMiddleware::class);

        $middleware->handle(
            $request,
            function () {
            }
        );

        $this->assertDatabaseHas(
            'tracker_agents',
            [
                'name' => TestCase::USER_AGENT_CHROME_WINDOWS_10,
                'browser' => 'Chrome',
                'browser_version' => '58.0.3029.110',
            ]
        );
    }

    public function test_device_windows_10_chrome_webkit()
    {
        $request = $this->createRequest(TestCase::USER_AGENT_CHROME_WINDOWS_10);

        $middleware = $this->app->make(RailtrackerMiddleware::class);

        $middleware->handle(
            $request,
            function () {
            }
        );

        $this->assertDatabaseHas(
            'tracker_devices',
            [
                'platform' => 'Windows',
                'platform_version' => '10.0',
                'kind' => 'desktop',
                'model' => 'WebKit',
                'is_mobile' => false
            ]
        );
    }

    public function test_track_language()
    {
        $request = $this->createRequest(TestCase::USER_AGENT_CHROME_WINDOWS_10);

        $middleware = $this->app->make(RailtrackerMiddleware::class);

        $middleware->handle(
            $request,
            function () {
            }
        );

        $this->assertDatabaseHas(
            'tracker_languages',
            [
                'preference' => 'en-gb',
                'language-range' => 'en-gb,en-us,en',
            ]
        );
    }

    public function test_request_no_route()
    {
        $userId = $this->createAndLogInNewUser();

        $url = 'https://www.testing.com/?test=1';
        $refererUrl = 'http://www.referer-testing.com/?test=2';
        $clientIp = '183.22.98.51';

        $request = $this->createRequest(
            TestCase::USER_AGENT_CHROME_WINDOWS_10,
            $url,
            $refererUrl,
            $clientIp
        );

        $request->setUserResolver(
            function () use ($userId) {
                return User::query()->find($userId);
            }
        );

        $middleware = $this->app->make(RailtrackerMiddleware::class);

        $middleware->handle(
            $request,
            function () {
            }
        );

        $this->assertDatabaseHas(
            'tracker_requests',
            [
                'user_id' => 1,
                'url_id' => 1,
                'route_id' => null,
                'device_id' => 1,
                'agent_id' => 1,
                'referer_url_id' => 2,
                'language_id' => 1,
                'geoip_id' => null,
                'client_ip' => $clientIp,
                'is_robot' => 0,
                'request_time' => Carbon::now()->timestamp,
            ]
        );
    }

    public function test_request_with_route()
    {
        $userId = $this->createAndLogInNewUser();

        $path = '/test/path/1';
        $query = 'test1=2&test2=3';
        $routeName = 'test.route.name';
        $routeAction = 'TestController@test';

        $url = 'https://www.testing.com' . $path . '?' . $query;
        $refererUrl = 'http://www.referer-testing.com/?test=2';
        $clientIp = '183.22.98.51';

        $route = $this->router->get(
            $path,
            [
                'as' => $routeName,
                'uses' => $routeAction
            ]
        );

        $request = $this->createRequest(
            TestCase::USER_AGENT_CHROME_WINDOWS_10,
            $url,
            $refererUrl,
            $clientIp
        );

        $request->setRouteResolver(
            function () use ($route) {
                return $route;
            }
        );

        $request->setUserResolver(
            function () use ($userId) {
                return User::query()->find($userId);
            }
        );

        $middleware = $this->app->make(RailtrackerMiddleware::class);

        $middleware->handle(
            $request,
            function () {
            }
        );

        $this->assertDatabaseHas(
            'tracker_requests',
            [
                'user_id' => 1,
                'url_id' => 1,
                'route_id' => 1,
                'device_id' => 1,
                'agent_id' => 1,
                'referer_url_id' => 2,
                'language_id' => 1,
                'geoip_id' => null,
                'client_ip' => $clientIp,
                'is_robot' => 0,
                'request_time' => Carbon::now()->timestamp,
            ]
        );
    }
}