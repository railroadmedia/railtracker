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
        $this->app->singleton(
            ExceptionHandler::class,
            Handler::class
        );

        $request = $this->randomRequest();
        $kernel = $this->app->make(HttpKernel::class);
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
        $kernel = $this->app->make(\Illuminate\Contracts\Http\Kernel::class);
        $this->app->singleton(
            \Illuminate\Contracts\Debug\ExceptionHandler::class,
            \Railroad\Railtracker\Tests\Resources\Exceptions\Handler::class
        );
        $this->expectsEvents(RequestTracked::class);

        $userId = $this->createAndLogInNewUser();
        
        $request = $this->createRequest(); // todo: replace with a "createRequestThatThrowsException" method
        $request->setUserResolver(function () use ($userId) {return User::query()->find($userId);});

        $kernel->pushMiddleware(RailtrackerMiddleware::class);
        $kernel->handle($request);

        $this->processTrackings();

        $numberOfEventsBefore = count($this->firedEvents);

        $now = Carbon::now();
        $hourLater = Carbon::now()->copy()->addHour();
        Carbon::setTestNow($hourLater);

        $request = $this->createRequest(); // todo: replace with a "createRequestThatThrowsException" method
        $request->setUserResolver(function () use ($userId) {return User::query()->find($userId);});

        $kernel->pushMiddleware(RailtrackerMiddleware::class);
        $kernel->handle($request);

        $this->processTrackings();

        $numberOfEventsAfter = count($this->firedEvents);
        $this->assertEquals(3, $numberOfEventsBefore);
        $this->assertEquals(5, $numberOfEventsAfter);

        // $aa_db = $this->seeDbWhileDebugging(); // handy for debugging

        $this->assertEquals($this->firedEvents[4]->userId, $userId);
        $this->assertEquals($this->firedEvents[4]->requestedOnDateTime->toDateTimeString(),$hourLater->toDateTimeString());

        foreach($this->firedEvents as $event){
            if(isset($event->usersPreviousRequestedOnDateTime)){
                $usersPreviousRequestedOnDateTime = $this->firedEvents[4]->usersPreviousRequestedOnDateTime;
            }
        }

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
