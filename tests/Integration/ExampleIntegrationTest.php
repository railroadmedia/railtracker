<?php

namespace Railroad\Railtracker\Tests\Integration;

use Railroad\Railtracker\Middleware\RailtrackerMiddleware;
use Railroad\Railtracker\Tests\TestCase;

class ExampleIntegrationTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
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
}