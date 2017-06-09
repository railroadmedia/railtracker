<?php

namespace Railroad\Railtracker\Tests\Integration;

use Railroad\Railtracker\Middleware\RailtrackerMiddleware;
use Railroad\Railtracker\Repositories\TrackerDataRepository;
use Railroad\Railtracker\Tests\Resources\Models\User;
use Railroad\Railtracker\Tests\TestCase;

class TrackerDataRepositoryTest extends TestCase
{
    /**
     * @var TrackerDataRepository
     */
    private $trackerDataRepository;

    protected function setUp()
    {
        parent::setUp();

        $this->trackerDataRepository = app(TrackerDataRepository::class);
    }

    public function test_get_requests_for_user()
    {
        $userId = $this->createAndLogInNewUser();

        $path = '/test/path/1';
        $query = 'test1=2&test2=3';
        $routeName = 'test.route.name';
        $routeAction = 'TestController@test';

        $request =
            $this->createRequest(
                TestCase::USER_AGENT_CHROME_WINDOWS_10,
                'https://www.test.com' . $path . '?' . $query
            );

        $request->setUserResolver(
            function () use ($userId) {
                return User::query()->find($userId);
            }
        );

        $route = $this->router->get(
            $path,
            [
                'as' => $routeName,
                'uses' => $routeAction
            ]
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

        $results = $this->trackerDataRepository->getRequestsForUser($userId);

        $this->assertArraySubset(
            [
                [
                    "protocol" => "https",
                    "domain" => "www.test.com",
                    "path" => "/test/path/1",
                    "query" => "test1=2&test2=3",
                    "route_name" => "test.route.name",
                    "route_action" => "TestController@test",
                    "agent" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36",
                    "agent_browser" => "Chrome",
                    "agent_browser_version" => "58.0.3029.110",
                    "device_type" => "desktop",
                    "device_model" => "WebKit",
                    "device_platform" => "Windows",
                    "device_platform_version" => "10.0",
                    "device_is_mobile" => "0",
                    "language_preference" => "en-gb",
                    "language_range" => "en-gb,en-us,en",
                ]
            ],
            $results
        );
    }
}