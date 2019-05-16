<?php

namespace Railroad\Railtracker\Tests\Integration\Trackers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Railroad\Railtracker\Middleware\RailtrackerMiddleware;
use Railroad\Railtracker\Services\ConfigService;
use Railroad\Railtracker\Tests\RailtrackerTestCase;

class ResponseTrackerTest extends RailtrackerTestCase
{
    public function test_track_response_status_code()
    {
        $request = $this->randomRequest();
        $response = $this->createResponse(200);

        $this->sendRequestAndCallProcessCommand($request, $response);

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

        $this->sendRequestAndCallProcessCommand($request, $response);

        $this->assertDatabaseHas(
            ConfigService::$tableResponseStatusCodes,
            [
                'code' => 404
            ]
        );
    }

    public function test_track_response()
    {
        $request = $this->randomRequest();
        $response = $this->createResponse(200);

        $this->sendRequestAndCallProcessCommand($request, $response);

        $this->assertDatabaseHas(
            ConfigService::$tableResponses,
            [
                'request_id' => 1,
                'status_code_id' => 1,
                'responded_on' => Carbon::now()->toDateTimeString(),
            ]
        );
    }
}