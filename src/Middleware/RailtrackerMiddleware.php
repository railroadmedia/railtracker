<?php

namespace Railroad\Railtracker\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Railroad\Railtracker\Trackers\RequestTracker;
use Railroad\Railtracker\Trackers\ResponseTracker;
use Cookie;
use Ramsey\Uuid\Uuid;
use Railroad\Railtracker\Services\ConfigService;

class RailtrackerMiddleware
{
    /**
     * @var RequestTracker
     */
    protected $requestTracker;

    /**
     * @var ResponseTracker
     */
    protected $responseTracker;

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
        $userId = $request->user();

        if(!in_array($request->path(), ConfigService::$requestExclusionPaths)) {
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

            if (is_null($userId) && (!array_key_exists('user', $_COOKIE)) && (!is_null($response))) {
                $this->setCookie($response);
            }
        } else {
            $response = $next($request);
        }

        return $response;
    }

    /**
     * Send a cookie with 'user' key to the response
     * @return mixed
     */
   protected function setCookie($response)
    {
        $key = Uuid::uuid4();
        return $response->withCookie(cookie()->forever('user', $key));
    }
}