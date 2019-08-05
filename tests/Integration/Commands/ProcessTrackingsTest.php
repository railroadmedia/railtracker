<?php

namespace Railroad\Railtracker\Tests\Integration\Commands;

use Railroad\Railtracker\Middleware\RailtrackerMiddleware;
use Railroad\Railtracker\Tests\RailtrackerTestCase;
use Railroad\Railtracker\Tests\Resources\Exceptions\Handler;
use Railroad\Railtracker\Trackers\RequestTracker;

class ProcessTrackingsTest extends RailtrackerTestCase
{
    /**
     * @var RailtrackerMiddleware
     */
    public $railtrackerMiddleware;

    protected function setUp()
    {
        parent::setUp();

        $this->railtrackerMiddleware = resolve(RailtrackerMiddleware::class);
    }

    public function test_track_response_status_code()
    {
        $this->go(10,10, false);
    }

    public function test_track_response_status_code_exceptions()
    {
        $this->go(10,10, false, [false, false, true]);
    }

    public function test_track_response_status_code_to_get_data()
    {
        $tStart = microtime(true);

        echo "test-size,scan-size,creation time,processing time,queries*\n";

        $this->go(100,20, false);
        $this->go(100,40, false);
        $this->go(100,60, false);
        $this->go(100,80, false);
        $this->go(100,100, false);

        $this->go(100,100, false);
        $this->go(100,80, false);
        $this->go(100,60, false);
        $this->go(100,40, false);
        $this->go(100,20, false);
        

        dd($this->seeDbWhileDebugging());
        
        $time = round(microtime(true) - $tStart, 2);
        $mRaw = floor($time);
        $sRaw = $time - ($mRaw * 60);
        $timePretty = $mRaw . ':' . $sRaw;
        echo "\n";
        echo 'test_track_response_status_code_to_get_data ran in: ' . $time . "\n";
    }

    private function go($testSize, $scanSize, $verbose = true, $throwExceptionsOn = [], $includeMemoryDiff = false)
    {
        $toDelete = $this->batchService->cache()->keys('*');
        if(!empty($toDelete)){
            $this->batchService->cache()->del($toDelete);
        }

        config()->set('railtracker.scan-size', $scanSize);

        $tStart = microtime(true);

        for ($i = 0; $i < $testSize; $i++) {

            $request = $this->createRequest();

            $throwException = $throwExceptionsOn[$i] ?? false;

            if($throwException){
                $this->throwExceptionDuringRequest($request);
            }else{
                $this->handleRequest($request);
            }
        }

        $tEnd = microtime(true);

        $creationTime = round(($tEnd - $tStart), 2);

        if($verbose){
            echo 'Time to generate and handle ' .
                $testSize .
                ' requests, and store them in redis: ' .
                $creationTime .
                ' seconds.' .
                "\n";
            echo 'Queries ran to generate and handle ' . $testSize . ' requests, and store them in redis: ' . $this->queryLogger->count() . "\n";
        }

        $mem01 = memory_get_usage();

        $tStart = microtime(true);

        $this->processTrackings();

        $tEnd = microtime(true);

        $mem02 = memory_get_usage();

        $processingTime = round(($tEnd - $tStart),2);

        if($verbose){
            echo 'Time to process ' .
                $testSize .
                ' requests and responses and store them in a fresh database: ' .
                $processingTime .
                ' seconds.' .
                "\n";
            echo 'Queries ran to store ' . $testSize . ' requests in database: ' . $this->queryLogger->count() . "\n";
        }

        $memoryDiff = $mem02 - $mem01;

        echo $testSize . ',' .
            config('railtracker.scan-size') . ',' .
            $creationTime . ',' .
            $processingTime . ',' .
            $this->queryLogger->count() .
            ($includeMemoryDiff ? $memoryDiff : '') . "\n";

        $this->expectNotToPerformAssertions();
    }

    public function test_memory_usage()
    {
        echo 'testSize' . ',' .
            'scan-size' . ',' .
            'creationTime' . ',' .
            'processingTime' . ',' .
            'queryLogger count' .
            'memoryDiff' . "\n";

        for($i = 0; $i < 100; $i++){
            $this->go(10, 10, false, [], true);
        }
    }

    public function test_memory_usage_version_two()
    {
        echo 'testSize' . ',' .
            'scan-size' . ',' .
            'creationTime' . ',' .
            'processingTime' . ',' .
            'queryLogger count' .
//            'memoryDiff' .
            "\n";

        for($i = 0; $i < 100; $i++){
            $mem01 = memory_get_usage();
            $this->go(10, 10, false);
            $mem02 = memory_get_usage();
            $memoryDiff[$i] = $mem02 - $mem01;
            if($i > 0){
                $memoryMetaDiff[$i] = $memoryDiff[$i] - $memoryDiff[$i - 1];
            }
        }

        var_export($memoryMetaDiff ?? []);
    }
}