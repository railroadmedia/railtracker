<?php

namespace Railroad\Railtracker\Tests\Functional\Trackers;

use Railroad\Railtracker\Services\ConfigService;
use Railroad\Railtracker\Services\IpDataApiSdkService;
use Railroad\Railtracker\Tests\RailtrackerTestCase;
use Railroad\Railtracker\Tests\Resources\IpDataApiStubDataProvider;

class RequestTrackerTest extends RailtrackerTestCase
{
    public function test_ipData_api_not_queried_for_already_known_ips()
    {
        $input = IpDataApiStubDataProvider::$INPUT;
        $output = collect(IpDataApiStubDataProvider::output());
        $expected = IpDataApiStubDataProvider::expectedInDatabase();

        // -------------------------------------------------------------------------------------------------------------

        $ipDataApiSdkServiceMock = $this
            ->getMockBuilder(IpDataApiSdkService::class)
            ->setMethods(['bulkRequest'])
            ->getMock();

        $arrayNotEmpty = $this->callback(function($array){
            return !empty($array);
        });

        $arrayEmpty = $this->callback(function($array){
            return empty($array);
        });

        $ipDataApiSdkServiceMock
            ->expects($this->at(0))
            ->method('bulkRequest')
            ->with($arrayNotEmpty)
            ->willReturn($output);

        /*
         * The "->with($arrayEmpty)" is the key point of this test—asserting that's there's no diff between ips in
         * second set of requests processed and ips recorded in first set of processing and then retrieved from table
         * when checking for existing.
         */
        $ipDataApiSdkServiceMock
            ->expects($this->at(1))
            ->method('bulkRequest')
            ->with($arrayEmpty)
            ->willReturn([]);

        app()->instance(IpDataApiSdkService::class, $ipDataApiSdkServiceMock);

        // first set of requests ---------------------------------------------------------------------------------------

        foreach($input as $ip){
            $request = $this->randomRequest($ip);
            $this->sendRequest($request);
        }

        try{
            $this->processTrackings();
        }catch(\Exception $exception){
            $this->fail($exception->getMessage());
        }

        foreach($expected as $expectedRow){
            $this->assertDatabaseHas(
                ConfigService::$tableGeoIP,
                $expectedRow
            );
        }
        // second set of requests --------------------------------------------------------------------------------------

        foreach($input as $ip){
            $request = $this->randomRequest($ip);
            $this->sendRequest($request);
        }

        try{
            $this->processTrackings();
        }catch(\Exception $exception){
            $this->fail($exception->getMessage());
        }

        foreach($expected as $expectedRow){
            $this->assertDatabaseHas(
                ConfigService::$tableGeoIP,
                $expectedRow
            );
        }
    }
}
