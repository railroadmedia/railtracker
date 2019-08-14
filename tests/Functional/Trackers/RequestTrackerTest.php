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
        $ipDataApiSdkServiceMock
            ->expects($this->at(1))
            ->method('bulkRequest')
            ->with($this->callback(function($array){
                return empty($array);
            }))
            ->willReturn([]);

        app()->instance(IpDataApiSdkService::class, $ipDataApiSdkServiceMock);

        // todo: Stub RequestRepostitory? (So you can add an expectation for getMostRecentRequestForEachIpAddress() to be called and on the second time to be passed an empty array)


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
        // todo: decide if a change is needed here since it's not really doing what we want-namely ensuring that the API is just called once...? But then maybe that doesn't matter and this is just a check that everything is still has expected...? Or might that be better addressed by comparing before-and-after the output from `$this->seeDbWhileDebugging()` for some tables...? Maybe just add a note that this isn't important and point emphasis up to setting the expectation for bulkRequest be passed an empty array the second time its called...?
        foreach($expectedInDB as $expectedRow){
            $this->assertDatabaseHas(
                config('railtracker.table_prefix') . 'requests',
                $expectedRow
            );
        }
    }

    public function test_ipData_api_not_queried_for_already_known_ips_lite()
    {
        $requests = collect();
        $expected = collect();
        $outputKeyedByIp = [];

        $input = IpDataApiStubDataProvider::$INPUT;
        $output = IpDataApiStubDataProvider::output();

        unset($input[3]);
        unset($input[4]);
        unset($input[5]);
        unset($input[6]);
        unset($input[7]);
        unset($input[8]);

        unset($output[3]);
        unset($output[4]);
        unset($output[5]);
        unset($output[6]);
        unset($output[7]);
        unset($output[8]);


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
        $ipDataApiSdkServiceMock
            ->expects($this->at(1))
            ->method('bulkRequest')
            ->with($this->callback(function($array){
                return empty($array);
            }))
            ->willReturn([]);

        app()->instance(IpDataApiSdkService::class, $ipDataApiSdkServiceMock);

        // todo: Stub RequestRepostitory? (So you can add an expectation for getMostRecentRequestForEachIpAddress() to be called and on the second time to be passed an empty array)


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
        // todo: decide if a change is needed here since it's not really doing what we want-namely ensuring that the API is just called once...? But then maybe that doesn't matter and this is just a check that everything is still has expected...? Or might that be better addressed by comparing before-and-after the output from `$this->seeDbWhileDebugging()` for some tables...? Maybe just add a note that this isn't important and point emphasis up to setting the expectation for bulkRequest be passed an empty array the second time its called...?
        foreach($expectedInDB as $expectedRow){
            $this->assertDatabaseHas(
                config('railtracker.table_prefix') . 'requests',
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
