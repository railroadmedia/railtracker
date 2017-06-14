<?php

namespace Railroad\Railtracker\Tests\Integration\Trackers;

use Carbon\Carbon;
use Railroad\Railtracker\Middleware\RailtrackerMiddleware;
use Railroad\Railtracker\Services\ConfigService;
use Railroad\Railtracker\Tests\TestCase;

class ResponseTrackerTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
    }

    public function test_track_response_status_code()
    {
        $request = $this->randomRequest();
        $response = $this->createResponse(200);

        $middleware = $this->app->make(RailtrackerMiddleware::class);

        $middleware->handle(
            $request,
            function () use ($response) {
                return $response;
            }
        );

        $this->assertDatabaseHas(
            ConfigService::$tableResponseStatusCodes,
            [
                'code' => 200,
            ]
        );
    }

    public function test_track_response_status_code_404()
    {
        $request = $this->randomRequest();
        $response = $this->createResponse(404);

        $middleware = $this->app->make(RailtrackerMiddleware::class);

        $middleware->handle(
            $request,
            function () use ($response) {
                return $response;
            }
        );

        $this->assertDatabaseHas(
            ConfigService::$tableResponseStatusCodes,
            [
                'code' => 404,
            ]
        );
    }

    public function test_track_response()
    {
        $request = $this->randomRequest();
        $response = $this->createResponse(200);

        $middleware = $this->app->make(RailtrackerMiddleware::class);

        $middleware->handle(
            $request,
            function () use ($response) {
                return $response;
            }
        );

        $this->assertDatabaseHas(
            ConfigService::$tableResponses,
            [
                'request_id' => '1',
                'status_code_id' => '1',
                'response_duration_ms' => '',
                'responded_on' => Carbon::now()->toDateTimeString(),
            ]
        );
    }
}