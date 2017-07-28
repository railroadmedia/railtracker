<?php

namespace Railroad\Railtracker\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Railroad\Railtracker\Services\ConfigService;
use Railroad\Railtracker\Trackers\RequestTracker;
use Railroad\Railtracker\Trackers\ResponseTracker;
use Ramsey\Uuid\Uuid;

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

    /**
     * RailtrackerMiddleware constructor.
     *
     * @param RequestTracker $requestTracker
     * @param ResponseTracker $responseTracker
     */
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
        $exclude = false;
        $cookie = null;

        foreach (ConfigService::$exclusionRegexPaths as $exclusionRegexPath) {
            if (preg_match($exclusionRegexPath, $request->path())) {
                $exclude = true;
            }
        }

        if (!$exclude) {

            // if no cookie is set and the user is logged out, create and attach one to the current request
            if (is_null($userId) && !$request->cookies->has(RequestTracker::$cookieKey)) {
                $cookieId = Uuid::uuid4()->toString();
                $cookie = cookie()->forever(RequestTracker::$cookieKey, $cookieId);

                $request->cookies->set(RequestTracker::$cookieKey, $cookieId);
            }

            // track the request
            try {
                $requestId = $this->requestTracker->trackRequest($request);
            } catch (Exception $exception) {
                error_log($exception);
            }

            $response = $next($request);

            // track the response after the application has handled it
            try {
                $this->responseTracker->trackResponse($response, $requestId);
            } catch (Exception $exception) {
                error_log($exception);
            }

            // set tracking cookie on response
            if (!empty($cookie) && !empty($response)) {
                $response->withCookie($cookie);
            }

        } else {
            $response = $next($request);
        }

        return $response;
    }
}