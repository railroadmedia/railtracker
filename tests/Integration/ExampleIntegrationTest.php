<?php

namespace Railroad\Railtracker\Tests\Integration;

use Illuminate\Http\Request;
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

        $request = Request::create('http://example.com/admin', 'GET');

        $middleware = $this->app->make(RailtrackerMiddleware::class);

        $response = $middleware->handle(
            $request,
            function () {
            }
        );

        $this->assertEquals($response, null);
    }
}