<?php

namespace Railroad\Railtracker\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Railroad\Railtracker\Trackers\RequestTracker;

class RailtrackerMiddleware
{
    /**
     * @var RequestTracker
     */
    private $requestTracker;

    public function __construct(RequestTracker $requestTracker)
    {
        $this->requestTracker = $requestTracker;
    }

    /**
     * Handle an incoming request.
     *
     * @param  Request $request
     * @param  Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        try {
            $this->requestTracker->trackRequest($request);
        } catch (Exception $exception) {
            error_log($exception);
        }

        $response = $next($request);

        try {
            $this->requestTracker->trackRequest($request);
        } catch (Exception $exception) {
            error_log($exception);
        }

        return $response;
    }
}