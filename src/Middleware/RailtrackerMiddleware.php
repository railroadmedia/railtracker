<?php

namespace Railroad\Railtracker\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Railroad\Railtracker\Services\Tracker;

class RailtrackerMiddleware
{
    /**
     * @var Guard
     */
    protected $auth;

    /**
     * @var Tracker
     */
    private $tracker;

    public function __construct(Guard $auth, Tracker $tracker)
    {
        $this->auth = $auth;
        $this->tracker = $tracker;
    }

    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $this->tracker->trackRequest($request);

        return $next($request);
    }
}