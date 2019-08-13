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

        /*
         * That the first is not empty but the second *is*... is the key point of this test.
         *
         * We're asserting that's the API isn't called because we don't need to because we got the data from our DB
         */

        // first *not* empty
        $ipDataApiSdkServiceMock
            ->expects($this->at(0))
            ->method('bulkRequest')
            ->with($this->callback(function($array){
                return !empty($array);
            }))
            ->willReturn($output);

        // second *is* empty
        $ipDataApiSdkServiceMock
            ->expects($this->at(1))
            ->method('bulkRequest')
            ->with($this->callback(function($array){
                return empty($array);
            }))
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

//    /**
//     * todo: write this test
//     */
//    public function test_process_no_keys()
//    {
//        $this->markTestIncomplete('todo');
//    }
}
