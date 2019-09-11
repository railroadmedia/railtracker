<?php

namespace Railroad\Railtracker\Tests\Resources\Exceptions;

use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Railroad\Railtracker\Trackers\ExceptionTracker;

class Handler extends ExceptionHandler
{
    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Exception $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        /**
         * @var $exceptionTracker ExceptionTracker
         */
        $exceptionTracker = app(ExceptionTracker::class);

        $exceptionTracker->trackException($request, $exception);

        return parent::render($request, $exception);
    }
}
