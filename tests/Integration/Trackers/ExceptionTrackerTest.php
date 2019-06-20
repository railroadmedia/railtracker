<?php

namespace Railroad\Railtracker\Tests\Integration\Trackers;

use Carbon\Carbon;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Auth\User;
use Railroad\Railtracker\Events\RequestTracked;
use Railroad\Railtracker\Middleware\RailtrackerMiddleware;
use Railroad\Railtracker\Services\ConfigService;
use Railroad\Railtracker\Tests\Resources\Exceptions\Handler;
use Railroad\Railtracker\Tests\RailtrackerTestCase;
use Railroad\Railtracker\Trackers\ExceptionTracker;
use Railroad\Railtracker\Trackers\RequestTracker;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ExceptionTrackerTest extends RailtrackerTestCase
{
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
