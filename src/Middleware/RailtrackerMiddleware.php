<?php

namespace Railroad\Railtracker\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Railroad\Railtracker\Services\BatchService;
use Railroad\Railtracker\ValueObjects\RequestVO;
use Ramsey\Uuid\Uuid;

class RailtrackerMiddleware
{
    /**
     * @var BatchService
     */
    private $batchService;

    public static $UUID;

    /**
     * RailtrackerMiddleware constructor.
     *
     * @param BatchService $batchService
     */
    public function __construct(
        BatchService $batchService
    ){
        $this->batchService = $batchService;
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     * @throws Exception
     */
    public function handle(Request $request, Closure $next)
    {
        // skip if disabled in the config
        if (config('railtracker.global_is_active') != true) {
            return $next($request);
        }

        // if the path is in the exclusions skip the request
        foreach (config('railtracker.exclusion_regex_paths') as $exclusionRegexPath) {
            if (preg_match($exclusionRegexPath, $request->path())) {
                return $next($request);
            }
        }

        // set visitor cookie if there isn't one already and there is no authenticated user
        if (empty($request->user()) && !$request->cookies->has(RequestVO::$visitorCookieKey)) {
            $cookieId = Uuid::uuid4()->toString();
            $cookie = cookie()->forever(RequestVO::$visitorCookieKey, $cookieId);

            $request->cookies->set(RequestVO::$visitorCookieKey, $cookieId);
        }

        // send request to cache
        try {
            $requestVO = new RequestVO($request);
            $this->batchService->storeRequest($requestVO);
        } catch (Exception $exception) {
            error_log($exception);
        }

        // send response to app
        /** @var Response $response */
        $response = $next($request);

        // add response data and resend request to cache
        if (!empty($requestVO)) {

            try {
                $requestVO->setResponseData($response);

                $this->batchService->storeRequest($requestVO);
            } catch (Exception $exception) {
                error_log($exception);
            }

        }

        // set tracking cookie on response
        if (!empty($cookie) && !empty($response)) {
            $response->withCookie($cookie);
        }

        return $response;
    }
}