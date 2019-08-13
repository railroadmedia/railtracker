<?php

namespace Railroad\Railtracker\Tests\Functional\Trackers;

use Illuminate\Support\Collection;
use Railroad\Railtracker\Services\ConfigService;
use Railroad\Railtracker\Services\IpDataApiSdkService;
use Railroad\Railtracker\Tests\RailtrackerTestCase;
use Railroad\Railtracker\Tests\Resources\IpDataApiStubDataProvider;
use Railroad\Railtracker\ValueObjects\RequestVO;

class RequestTrackerTest extends RailtrackerTestCase
{
    public function test_ipData_api_not_queried_for_already_known_ips()
    {
        $requests = collect();
        $expected = collect();
        $outputKeyedByIp = [];

        $input = IpDataApiStubDataProvider::$INPUT;
        $output = IpDataApiStubDataProvider::output();

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
            ->willReturn(collect($output));

        // second *is* empty
        // todo: UNCOMMENT
        // todo: UNCOMMENT
        // todo: UNCOMMENT
        // todo: UNCOMMENT
        // todo: UNCOMMENT
        // todo: UNCOMMENT
        // todo: UNCOMMENT
        // todo: UNCOMMENT
        // todo: UNCOMMENT
        // todo: UNCOMMENT
        // todo: UNCOMMENT
        // todo: UNCOMMENT
        // todo: UNCOMMENT
//        $ipDataApiSdkServiceMock
//            ->expects($this->at(1))
//            ->method('bulkRequest')
//            ->with($this->callback(function($array){
//                return empty($array);
//            }))
//            ->willReturn([]);

        app()->instance(IpDataApiSdkService::class, $ipDataApiSdkServiceMock);

        // first set of requests ---------------------------------------------------------------------------------------

        foreach($output as $dataForIp){
            $outputKeyedByIp[$dataForIp['ip']] = $dataForIp;
        }

        // create requests and corresponding DB expectations for the results of their processing
        foreach($input as $ip){
            $request = $this->randomRequest($ip);
            $requests->push($request);

            $requestVO = new RequestVO($request);
            $requestVO->setIpDataFromApiResult($outputKeyedByIp[$ip]);
            $expected->push($requestVO);
        }

        foreach($requests as $request){
            $this->sendRequest($request);
        }

        try{
            $this->processTrackings();
        }catch(\Exception $exception){
            $this->fail($exception->getMessage());
        }

        // todo: now assert for ~~two~~ *multiple* tables: requests and ip_addresses... but then also all the other "ip_..." assoication tables?

        $expectedInDB = IpDataApiStubDataProvider::expectedInDatabase($expected);

        foreach($expectedInDB as $expectedRow){
            $this->assertDatabaseHas(
                config('railtracker.table_prefix') . 'requests',
                $expectedRow
            );
        }

        return true; // todo: ==================================== pick up here ====================================
        return true; // todo: ==================================== pick up here ====================================
        return true; // todo: ==================================== pick up here ====================================
        return true; // todo: ==================================== pick up here ====================================
        return true; // todo: ==================================== pick up here ====================================
        return true; // todo: ==================================== pick up here ====================================
        return true; // todo: ==================================== pick up here ====================================
        return true; // todo: ==================================== pick up here ====================================
        return true; // todo: ==================================== pick up here ====================================
        return true; // todo: ==================================== pick up here ====================================

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

        // todo: now assert for ~~two~~ *multiple* tables: requests and ip_addresses... but then also all the other "ip_..." assoication tables?
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
