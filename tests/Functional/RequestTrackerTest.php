<?php

namespace Railroad\Railtracker\Tests\Functional\Trackers;

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

        // first set of requests ---------------------------------------------------------------------------------------

        // create requests and corresponding DB expectations for the results of their processing
        foreach($input as $ip){
            $request = $this->randomRequest($ip);
            $requests->push($request);

            $requestVO = new RequestVO($request);
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

        $expectedInDatabase = IpDataApiStubDataProvider::expectedInDatabase($expected, $output);

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

        $expectedInDatabase = IpDataApiStubDataProvider::expectedInDatabase($expected, $outputAll);

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
            $expectedTwo->push($requestVO);
            $this->sendRequest($request);
        }

        $expectedInDatabaseTwo = IpDataApiStubDataProvider::expectedInDatabase($expectedTwo, $outputAll);

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

    /**
     * @doesNotPerformAssertions
     */
    public function test_process_no_keys()
    {
        try{
            $this->processTrackings();
        }catch(\Exception $exception){
            $this->fail($exception->getMessage());
        }
    }
}
