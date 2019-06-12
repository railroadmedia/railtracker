<?php

namespace Railroad\Railtracker\Tests\Integration\Commands;

use Railroad\Railtracker\Middleware\RailtrackerMiddleware;
use Railroad\Railtracker\Tests\RailtrackerTestCase;

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
//        $this->go(100,10, false);
//        $this->go(100,20, false);
//        $this->go(100,30, false);
//        $this->go(100,40, false);
//        $this->go(100,50, false);
//        $this->go(100,60, false);
//        $this->go(100,70, false);
//        $this->go(100,80, false);
//        $this->go(100,90, false);
        $this->go(10,10, false);
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

        $time = round(microtime(true) - $tStart, 2);
        $mRaw = floor($time);
        $sRaw = $time - ($mRaw * 60);
        $timePretty = $mRaw . ':' . $sRaw;
        echo "\n";
        echo 'test_track_response_status_code_to_get_data ran in: ' . $timePretty . "\n";
    }

    private function go($testSize, $scanSize, $verbose = true)
    {
        $toDelete = $this->batchService->cache()->keys('*');
        if(!empty($toDelete)){
            $this->batchService->cache()->del($toDelete);
        }

        config()->set('railtracker.scan-size', $scanSize);

        $tStart = microtime(true);

        for ($i = 0; $i < $testSize; $i++) {

            $request = $this->randomRequest();

            $response = $this->createResponse(200);

            $next = function () use ($response) {
                return $response;
            };

            $this->railtrackerMiddleware->handle($request, $next);
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

        $tStart = microtime(true);

        $this->processTrackings();

        $tEnd = microtime(true);

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

        echo $testSize . ',' .
            config('railtracker.scan-size') . ',' .
            $creationTime . ',' .
            $processingTime . ',' .
            $this->queryLogger->count() . "\n";

        $this->expectNotToPerformAssertions();


    }
}