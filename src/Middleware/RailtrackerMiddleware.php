<?php

namespace Railroad\Railtracker\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Railroad\Railtracker\Services\BatchService;
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
     * @var BatchService
     */
    private $batchService;

    /**
     * RailtrackerMiddleware constructor.
     *
     * @param RequestTracker $requestTracker
     * @param ResponseTracker $responseTracker
     * @param BatchService $batchService
     */
    public function __construct(
        RequestTracker $requestTracker,
        ResponseTracker $responseTracker,
        BatchService $batchService
    ) {
        $this->requestTracker = $requestTracker;
        $this->responseTracker = $responseTracker;
        $this->batchService = $batchService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  Request $request
     * @param  Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (config('railtracker.global_is_active') != true) {
            return $next($request);
        }

        $response = null;
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
            
            RequestTracker::$uuid = Uuid::uuid4()->toString();

            if (is_null($userId) && !$request->cookies->has(RequestTracker::$cookieKey)) {
                $cookieId = Uuid::uuid4()->toString();
                $cookie = cookie()->forever(RequestTracker::$cookieKey, $cookieId);

                $request->cookies->set(RequestTracker::$cookieKey, $cookieId);
            }

            try {
                $serializedRequestEntity = $this->requestTracker->serializedFromHttpRequest($request);
                $this->batchService->addToBatch($serializedRequestEntity, $serializedRequestEntity['uuid']);
            } catch (Exception $exception) {
                error_log($exception);
            }

            /** @var Response $response */
            $response = $next($request);

            try {
                $responseData = $this->responseTracker->serializedFromHttpResponse($response);
                $this->batchService->addToBatch($responseData, RequestTracker::$uuid);

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