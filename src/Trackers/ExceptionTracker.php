<?php

namespace Railroad\Railtracker\Trackers;

use Exception;
use Illuminate\Cache\Repository;
use Illuminate\Cookie\CookieJar;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Railroad\Railtracker\Services\BatchService;
use Railroad\Railtracker\ValueObjects\ExceptionVO;
use Railroad\Railtracker\ValueObjects\RequestVO;

class ExceptionTracker extends TrackerBase
{
    public function __construct(
        DatabaseManager $databaseManager,
        Router $router,
        CookieJar $cookieJar,
        BatchService $batchService,
        Repository $cache = null
    ){
        parent::__construct(
            $databaseManager,
            $router,
            $cookieJar,
            $batchService,
            $cache
        );
        $this->batchService = $batchService;
    }

    /**
     * @param Request $request
     * @param Exception $exception
     * @return void
     */
    public function trackException(Request $request, Exception $exception)
    {
        try {
            $exceptionVO = new ExceptionVO($exception, RequestVO::$UUID);
            $this->batchService->storeException($exceptionVO);
        } catch (Exception $exception) {
            error_log($exception);
        }
    }
}
