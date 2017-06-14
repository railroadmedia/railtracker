<?php

namespace Railroad\Railtracker\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Railroad\Railtracker\Trackers\RequestTracker;
use Railroad\Railtracker\Trackers\ResponseTracker;

class RailtrackerMiddleware
{
    /**
     * @var RequestTracker
     */
    private $requestTracker;

    /**
     * @var ResponseTracker
     */
    private $responseTracker;

    public function __construct(RequestTracker $requestTracker, ResponseTracker $responseTracker)
    {
        $this->requestTracker = $requestTracker;
        $this->responseTracker = $responseTracker;
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
        $requestId = null;

        try {
            $requestId = $this->requestTracker->trackRequest($request);
        } catch (Exception $exception) {
            error_log($exception);
        }

        $response = $next($request);

        try {
            $this->responseTracker->trackResponse($response, $requestId);
        } catch (Exception $exception) {
            error_log($exception);
        }

        return $response;
    }
}