<?php namespace Railroad\Railtracker\Tests\Functional;

use Illuminate\Database\DatabaseManager;
use Railroad\Railtracker\Console\Commands\ProcessTrackings;
use Railroad\Railtracker\Managers\RailtrackerEntityManager;
use Railroad\Railtracker\Repositories\RequestRepository;
use Railroad\Railtracker\Services\BatchService;
use Railroad\Railtracker\Services\ConfigService;
use Railroad\Railtracker\Services\IpDataApiSdkService;
use Railroad\Railtracker\Tests\RailtrackerTestCase;
use Railroad\Railtracker\Trackers\ExceptionTracker;
use Railroad\Railtracker\Trackers\RequestTracker;
use Railroad\Railtracker\Trackers\ResponseTracker;
use Carbon\Carbon;

// from ExceptionTrackerTest
// todo: organize|cull|tidy|whatever

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Auth\User;
use Railroad\Railtracker\Events\RequestTracked;
use Railroad\Railtracker\Middleware\RailtrackerMiddleware;
use Railroad\Railtracker\Tests\Resources\Exceptions\Handler;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProcessTrackingsTest extends RailtrackerTestCase
{
    public function test_clear_already_processed_uuids()
    {
        // todo: complete|fix
        $this->markTestIncomplete('WIP');

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
                app(RequestRepository::class),
            ])
            ->getMock();

//        $processTrackingsMock
//            ->expects($this->once())
//            ->method('incrementCountersForOutputMessage')
//            ->with([
//                'requests' => 0,
//                'reqExc' => 0,
//                'responses' => 0,
//            ])
//            ->willReturn([
//                    'requests' => 1,
//                    'reqExc' => 0,
//                    'responses' => 1,
//            ]);

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
        // todo: complete|fix
        $this->markTestIncomplete('WIP');

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
                app(RequestRepository::class),
            ])
            ->getMock();

//        $processTrackingsMock
//            ->expects($this->exactly(2))
//            ->method('incrementCountersForOutputMessage')
//            ->with([
//                'requests' => 0,
//                'reqExc' => 0,
//                'responses' => 0,
//            ])
//            ->willReturn([
//                    'requests' => 1,
//                    'reqExc' => 0,
//                    'responses' => 1,
//            ]);

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

    // --------------------------------------------------------------------------------------------
    // response tracking tests (these were in Integration\Trackers\ResponseTrackerTest, should but this file is under
    // the Functional namespace. Is that okay or should a change be made?
    // --------------------------------------------------------------------------------------------

    public function test_track_response_status_code()
    {
        // todo: fix
        $this->markTestIncomplete('Inconsistent results. Passes randomly.');

        $request = $this->randomRequest();
        $response = $this->createResponse(200);

        $this->sendRequest($request, $response);
        $this->processTrackings();

        $this->assertDatabaseHas(
            config('railtracker.table_prefix') . 'requests',
            [
                'response_status_code' => 200,
            ]
        );
    }

    public function test_track_response_status_code_404()
    {
        // todo: complete|fix
        $this->markTestIncomplete('WIP');

        $request = $this->randomRequest();
        $response = $this->createResponse(404);

        $this->sendRequest($request, $response);
        $this->processTrackings();

        $this->assertDatabaseHas(
            ConfigService::$tableResponseStatusCodes,
            [
                'code' => 404
            ]
        );
    }

    public function test_track_response()
    {
        // todo: complete|fix
        $this->markTestIncomplete('WIP');

        $request = $this->randomRequest();
        $response = $this->createResponse(200);

        $this->sendRequest($request, $response);
        $this->processTrackings();

        $this->assertDatabaseHas(
            ConfigService::$tableResponses,
            [
                'request_id' => 1,
                'status_code_id' => 1,
                'responded_on' => Carbon::now()->toDateTimeString(),
            ]
        );
    }

    // From ExceptionTrackerTest

    public function test_track_404_exception()
    {
        app()->singleton(
            ExceptionHandler::class,
            Handler::class
        );

        $request = $this->randomRequest();
        $kernel = app()->make(HttpKernel::class);
        $kernel->pushMiddleware(RailtrackerMiddleware::class);
        $kernel->handle($request);

        try {
            $this->processTrackings();
        }catch(\Exception $exception){
            $this->fail(
                'RailtrackerTestCase::processTrackings threw exception with message: "' . $exception->getMessage() . '"'
            );
        }

        $this->assertDatabaseHas(
            ConfigService::$tableExceptions,
            [
                'exception_class' => NotFoundHttpException::class,
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableRequestExceptions,
            [
                'request_id' => 1,
                'exception_id' => 1,
            ]
        );
    }

    public function test_track_multiple_exceptions()
    {
        app()->singleton(
            \Illuminate\Contracts\Debug\ExceptionHandler::class,
            \Railroad\Railtracker\Tests\Resources\Exceptions\Handler::class
        );
        $this->expectsEvents(RequestTracked::class);

        $userId = $this->createAndLogInNewUser();
        $request = $this->createRequest();
        $request->setUserResolver(function () use ($userId) {return User::query()->find($userId);});
        $this->throwExceptionDuringRequest($request);

        $this->processTrackings();

        $numberOfEventsBefore = count($this->firedEvents);

        $now = Carbon::now();
        $hourLater = Carbon::now()->copy()->addHour();
        Carbon::setTestNow($hourLater);

        $request = $this->createRequest();
        $request->setUserResolver(function () use ($userId) {return User::query()->find($userId);});
        $this->throwExceptionDuringRequest($request);

        $this->processTrackings();

        $numberOfEventsAfter = count($this->firedEvents);
        $this->assertEquals(2, $numberOfEventsBefore);
        $this->assertEquals(3, $numberOfEventsAfter);

        $eventWithSecondRequest = $this->firedEvents[2];

        $this->assertEquals($eventWithSecondRequest->userId, $userId);
        $this->assertEquals($eventWithSecondRequest->requestedOnDateTime->toDateTimeString(),$hourLater->toDateTimeString());

        $usersPreviousRequestedOnDateTime = $eventWithSecondRequest->usersPreviousRequestedOnDateTime;

        $this->assertEquals(
            $usersPreviousRequestedOnDateTime ?? 'Not found in any of the fired events.',
            $now->toDateTimeString()
        );

        $this->assertDatabaseHas(
            ConfigService::$tableExceptions,
            [
                'exception_class' => \Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableRequests,
            [
                'id' => '1',
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableRequests,
            [
                'id' => '2',
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableRequestExceptions,
            [
                'request_id' => 1,
                'exception_id' => 1,
            ]
        );


        $this->assertDatabaseHas(
            ConfigService::$tableRequestExceptions,
            [
                'request_id' => 2,
                'exception_id' => 2,
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableResponses,
            [
                'request_id' => 1,
                'status_code_id' => 1,
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableResponses,
            [
                'request_id' => 2,
                'status_code_id' => 1,
            ]
        );
    }
}
