<?php

namespace Railroad\Railtracker\Middleware;

use Closure;
use Exception;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Railroad\Railtracker\Services\RequestTracker;

class RailtrackerMiddleware
{
    /**
     * @var Guard
     */
    protected $auth;

    /**
     * @var RequestTracker
     */
    private $requestTracker;

    public function __construct(Guard $auth, RequestTracker $requestTracker)
    {
        $this->auth = $auth;
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
        $response = $next($request);

        try {
            $this->requestTracker->trackRequest($request, app(Repository::class));
        } catch (Exception $exception) {
            error_log($exception);
        }

        return $response;
    }
}