<?php

use Illuminate\Database\DatabaseManager;
use Railroad\Railtracker\Console\Commands\ProcessTrackings;
use Railroad\Railtracker\Managers\RailtrackerEntityManager;
use Railroad\Railtracker\Services\BatchService;
use Railroad\Railtracker\Services\ConfigService;
use Railroad\Railtracker\Services\IpDataApiSdkService;
use Railroad\Railtracker\Tests\RailtrackerTestCase;
use Railroad\Railtracker\Trackers\ExceptionTracker;
use Railroad\Railtracker\Trackers\RequestTracker;
use Railroad\Railtracker\Trackers\ResponseTracker;

class ProcessTrackingsTest extends RailtrackerTestCase
{
    public function test_clear_already_processed_uuids()
    {
        // -------------------------------------------------------------------------------------------------------------
        // part one - set up mocks -------------------------------------------------------------------------------------
        // -------------------------------------------------------------------------------------------------------------

        $batchServiceMock = $this->getMockBuilder(BatchService::class)
            ->setMethods(['forget'])
            ->getMock();

        $batchServiceMock
            ->expects($this->exactly(2))
            ->method('forget')
            ->with(
                $this->callback(function($keys){
                    return !empty($keys);
                })
            );

        app()->instance(BatchService::class, $batchServiceMock);

        /*
         * This is the key (no pun intended) to this test. If the already-processed key is detected by the
         * "detectAndRemoveAccidentallyRemainingKeys" method (of ProcessTrackings), then the
         * `if(empty($keys) continue;` clause will prevent the second "processing run" from happening, and thus
         * The "incrementCountersForOutputMessage" method from being called a second time.
         *
         * This test is a little brittle though perhaps because anything else that makes the causes the script to not
         * hit that method call will also result in it not passing... perhaps....?
         */

        $processTrackingsMock = $this->getMockBuilder(ProcessTrackings::class)
            ->setMethods(['incrementCountersForOutputMessage'])
            ->setConstructorArgs([
                app(BatchService::class),
                app(RequestTracker::class),
                app(ExceptionTracker::class),
                app(ResponseTracker::class),
                app(RailtrackerEntityManager::class),
                app(IpDataApiSdkService::class),
                app(DatabaseManager::class),
            ])
            ->getMock();

        $processTrackingsMock
            ->expects($this->once())
            ->method('incrementCountersForOutputMessage')
            ->with([
                'requests' => 0,
                'reqExc' => 0,
                'responses' => 0,
            ])
            ->willReturn([
                    'requests' => 1,
                    'reqExc' => 0,
                    'responses' => 1,
            ]);

        app()->instance(ProcessTrackings::class, $processTrackingsMock);

        // -------------------------------------------------------------------------------------------------------------
        // part two - requests -----------------------------------------------------------------------------------------
        // -------------------------------------------------------------------------------------------------------------

        $request = $this->randomRequest();

        $this->sendRequest($request);

        // first processing

        try {
            $this->processTrackings();
        } catch (\Exception $exception) {
            $this->fail(
                'RailtrackerTestCase::processTrackings threw exception with message: "' . $exception->getMessage() . '"'
            );
        }

        $this->assertDatabaseMissing(
            ConfigService::$tableRequests,
            [
                'id' => 2,
            ]
        );

        // ensure test's mocking of BatchService "forget" method successfully prevented the forgetting of key

        $keys = null;
        $redisIterator = null;
        while ($redisIterator !== 0) {
            $scanResult =
                $this->batchService->cache()->scan(
                    $redisIterator,
                    [
                        'MATCH' => $this->batchService->batchKeyPrefix . '*',
                        'COUNT' => config('railtracker.scan-size', 1000)
                    ]
                );
            $redisIterator = (integer)$scanResult[0];
            $keys = $scanResult[1];
        }

        if(empty($keys)){
            $this->fail(
                'Expected key in cache still, but was empty. Thus, test will not accurately test target behaviour'
            );
        }

        // second processing

        try {
            $this->processTrackings();
        } catch (\Exception $exception) {
            $this->fail(
                'RailtrackerTestCase::processTrackings threw exception with message: "' . $exception->getMessage() . '"'
            );
        }

        /*
         * The core of this test is that ProcessTrackings "incrementCountersForOutputMessage" will not run a second
         * time
         */

        $this->assertDatabaseMissing(
            ConfigService::$tableRequests,
            [
                'id' => 2,
            ]
        );
    }

    public function test_clear_already_processed_uuids_control_case_disable_key_method()
    {
        // -------------------------------------------------------------------------------------------------------------
        // part one - set up mocks -------------------------------------------------------------------------------------
        // -------------------------------------------------------------------------------------------------------------

        $batchServiceMock = $this->getMockBuilder(BatchService::class)
            ->setMethods(['forget'])
            ->getMock();

        $batchServiceMock
            ->expects($this->exactly(2))
            ->method('forget')
            ->with(
                $this->callback(function($keys){
                    return !empty($keys);
                })
            );

        app()->instance(BatchService::class, $batchServiceMock);

        /*
         * The key here is that we call "detectAndRemoveAccidentallyRemainingKeys" twice, but because we mock it and
         * kill it's removal of an already-processed key from the list of keys to be processed, the "processing run"
         * happens again, and "incrementCountersForOutputMessage" is thus called twice—rather than the once as in a
         * case where "detectAndRemoveAccidentallyRemainingKeys" functions properly.
         */

        $processTrackingsMock = $this->getMockBuilder(ProcessTrackings::class)
            ->setMethods(['incrementCountersForOutputMessage', 'detectAndRemoveAccidentallyRemainingKeys'])
            ->setConstructorArgs([
                app(BatchService::class),
                app(RequestTracker::class),
                app(ExceptionTracker::class),
                app(ResponseTracker::class),
                app(RailtrackerEntityManager::class),
                app(IpDataApiSdkService::class),
                app(DatabaseManager::class),
            ])
            ->getMock();

        $processTrackingsMock
            ->expects($this->exactly(2))
            ->method('incrementCountersForOutputMessage')
            ->with([
                'requests' => 0,
                'reqExc' => 0,
                'responses' => 0,
            ])
            ->willReturn([
                    'requests' => 1,
                    'reqExc' => 0,
                    'responses' => 1,
            ]);

        $processTrackingsMock
            ->expects($this->exactly(2))
            ->method('detectAndRemoveAccidentallyRemainingKeys')
            ->with($this->callback(
                function($arg)
                {
                    $matches = strpos($arg[0], $this->batchService->prefixForSet) !== false;
                    return $matches;
                }
            ))
            ->will($this->returnCallback(
                function($arg)
                {
                    return $arg;
                }
            ));

        app()->instance(ProcessTrackings::class, $processTrackingsMock);

        // -------------------------------------------------------------------------------------------------------------
        // part two - requests -----------------------------------------------------------------------------------------
        // -------------------------------------------------------------------------------------------------------------

        $request = $this->randomRequest();

        $this->sendRequest($request);

        // first processing

        try {
            $this->processTrackings();
        } catch (\Exception $exception) {
            $this->fail(
                'RailtrackerTestCase::processTrackings threw exception with message: "' . $exception->getMessage() . '"'
            );
        }

        $this->assertDatabaseMissing(
            ConfigService::$tableRequests,
            [
                'id' => 2,
            ]
        );

        // ensure test's mocking of BatchService "forget" method successfully prevented the forgetting of key

        $keys = null;
        $redisIterator = null;
        while ($redisIterator !== 0) {
            $scanResult =
                $this->batchService->cache()->scan(
                    $redisIterator,
                    [
                        'MATCH' => $this->batchService->batchKeyPrefix . '*',
                        'COUNT' => config('railtracker.scan-size', 1000)
                    ]
                );
            $redisIterator = (integer)$scanResult[0];
            $keys = $scanResult[1];
        }

        if(empty($keys)){
            $this->fail(
                'Expected key in cache still, but was empty. Thus, test will not accurately test target behaviour'
            );
        }

        // second processing

        try {
            $this->processTrackings();
        } catch (\Exception $exception) {
            $this->fail(
                'RailtrackerTestCase::processTrackings threw exception with message: "' . $exception->getMessage() . '"'
            );
        }

        /*
         * The core of this test is that ProcessTrackings "incrementCountersForOutputMessage" will not run a second
         * time
         */

        $this->assertDatabaseMissing(
            ConfigService::$tableRequests,
            [
                'id' => 2,
            ]
        );
    }
}
