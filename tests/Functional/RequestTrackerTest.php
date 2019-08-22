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

        $expectedInDatabase = IpDataApiStubDataProvider::expectedInDatabase($expected);

        foreach($expectedInDatabase as $expectedRow){
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
        foreach($expectedInDatabase as $expectedRow){
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

        $input = [
            $input[0],
            $input[1],
            $input[2],
        ];

        $output = [
            $output[0],
            $output[1],
            $output[2],
        ];

        // -------------------------------------------------------------------------------------------------------------

        $ipDataApiSdkServiceMock = $this
            ->getMockBuilder(IpDataApiSdkService::class)
            ->setMethods(['bulkRequest'])
            ->getMock();

        /*
         * Note that the first is *not* empty but the second *is*... this is the key point of this test.
         *
         * We're asserting that's the API is *not* called the second time around because we it's not needed as we
         * in our database already have the specifics corresponding to that IP address.
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

        $expectedInDatabase = IpDataApiStubDataProvider::expectedInDatabase($expected);

        foreach($expectedInDatabase as $expectedRow){
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

        foreach($expectedInDatabase as $expectedRow){
            $this->assertDatabaseHas(
                config('railtracker.table_prefix') . 'requests',
                $expectedRow
            );
        }
    }

    public function test_ipData_api_only_some_ips_already_known()
    {
        $onlyTestBulkRequestParams = true; // debugging aid - manually flip this to false to test only the params-as-expected assertions below.

        $requests = collect();
        $expected = collect();

        $requestsTwo = collect();
        $expectedTwo = collect();

        $outputKeyedByIp = [];

        $inputAll = IpDataApiStubDataProvider::$INPUT;
        $outputAll = IpDataApiStubDataProvider::output();

        $inputFirst = [
            $inputAll[0],
            $inputAll[1],
            $inputAll[2],
        ];

        $inputSecond = [
            $inputAll[3],
            $inputAll[4],
            $inputAll[5],
        ];

        $outputFirst = [
            $outputAll[0],
            $outputAll[1],
            $outputAll[2],
        ];

        $outputSecond = [
            $outputAll[3],
            $outputAll[4],
            $outputAll[5],
        ];

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
            ->with($this->callback(function($params) use ($inputFirst){
                $paramValues = array_values($params);
                sort($paramValues);
                return $paramValues === $inputFirst;
            }))
            ->willReturn(collect($outputFirst));

        // second *is* empty
        $ipDataApiSdkServiceMock
            ->expects($this->at(1))
            ->method('bulkRequest')
            ->with($this->callback(function($params) use ($inputSecond){
                $paramValues = array_values($params);
                sort($paramValues);
                return $paramValues === $inputSecond;
            }))
            ->willReturn(collect($outputSecond));

        app()->instance(IpDataApiSdkService::class, $ipDataApiSdkServiceMock);

        // first set of requests ---------------------------------------------------------------------------------------

        foreach($outputFirst as $dataForIp){
            $outputKeyedByIp[$dataForIp['ip']] = $dataForIp;
        }

        // create requests and corresponding DB expectations for the results of their processing
        foreach($inputFirst as $ip){
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

        $expectedInDatabase = IpDataApiStubDataProvider::expectedInDatabase($expected);

        if(!$onlyTestBulkRequestParams){
            foreach($expectedInDatabase as $expectedRow){
                $this->assertDatabaseHas(
                    config('railtracker.table_prefix') . 'requests',
                    $expectedRow
                );
            }
        }

        // second set of requests --------------------------------------------------------------------------------------

        foreach($inputFirst as $ip){
            $request = $this->randomRequest($ip);
            $requestsTwo->push($request);
            $requestVO = new RequestVO($request);
            $requestVO->setIpDataFromApiResult($outputKeyedByIp[$ip]);
            $expectedTwo->push($requestVO);
            $this->sendRequest($request);
        }

        $expectedInDatabaseTwo = IpDataApiStubDataProvider::expectedInDatabase($expectedTwo);

        $expectedInDatabaseBoth = array_merge($expectedInDatabase, $expectedInDatabaseTwo);

        foreach($inputSecond as $ip){
            $request = $this->randomRequest($ip);
            $this->sendRequest($request);
        }

        try{
            $this->processTrackings();
        }catch(\Exception $exception){
            $this->fail($exception->getMessage());
        }

        if(!$onlyTestBulkRequestParams) {
            foreach ($expectedInDatabaseBoth as $expectedRow) {
                $this->assertDatabaseHas(
                    config('railtracker.table_prefix') . 'requests',
                    $expectedRow
                );
            }
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
