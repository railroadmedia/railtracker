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

    /** @test */
    public function example()
    {
        $userId = $this->createAndLogInNewUser();

        $request = $this->createRequest(TestCase::USER_AGENT_CHROME_WINDOWS_10);

        $middleware = $this->app->make(RailtrackerMiddleware::class);

        $response = $middleware->handle(
            $request,
            function () {
            }
        );

        $this->assertEquals($response, null);
    }
}