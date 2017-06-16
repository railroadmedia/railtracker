<?php

namespace Railroad\Railtracker\Tests\Integration\Trackers;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Railroad\Railtracker\Middleware\RailtrackerMiddleware;
use Railroad\Railtracker\Services\ConfigService;
use Railroad\Railtracker\Tests\Resources\Exceptions\Handler;
use Railroad\Railtracker\Tests\RailtrackerTestCase;
use Railroad\Railtracker\Trackers\ExceptionTracker;
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
}