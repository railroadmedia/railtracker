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
                $this->batchService->addToBatch($serializedRequestEntity, 'request', $serializedRequestEntity['uuid']);
            } catch (Exception $exception) {
                error_log($exception);
            }

            /** @var Response $response */
            $response = $next($request);

            // ↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓ temporary debugging aid, remove anytime ↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓
            // ↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓ temporary debugging aid, remove anytime ↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓
            // ↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓ temporary debugging aid, remove anytime ↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓

            $class1 = gettype($response) === \Symfony\Component\HttpFoundation\Response::class; // DEBUG AID
            $class2 = gettype($response) === Response::class; // DEBUG AID
            $isExpectedClass = $class1 || $class2; // DEBUG AID

            if(!$isExpectedClass){
                $requestDump = var_export($serializedRequestEntity ?? '$serializedRequestEntity not set!', true); // DEBUG AID
                $responseDump = var_export($response, true); // DEBUG AID
                $dump = '$requestDump: ```' . $requestDump . '```... "$responseDump: ```' . $responseDump . '```'; // DEBUG AID
                error_log('Response is NOT expected class in RailtrackerMiddleware. Request: ' . $dump); // DEBUG AID
            }else{
                error_log('Response is expected class in RailtrackerMiddleware.');
            }

            // ↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑ temporary debugging aid, remove anytime ↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑
            // ↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑ temporary debugging aid, remove anytime ↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑
            // ↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑ temporary debugging aid, remove anytime ↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑

            try {
                $responseData = $this->responseTracker->serializedFromHttpResponse($response);
                $this->batchService->addToBatch($responseData, 'response', RequestTracker::$uuid);

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