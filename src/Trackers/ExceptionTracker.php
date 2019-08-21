<?php

namespace Railroad\Railtracker\Trackers;

use Exception;
use Illuminate\Cache\Repository;
use Illuminate\Cookie\CookieJar;
use Illuminate\Database\DatabaseManager;
use Illuminate\Routing\Router;
use Railroad\Doctrine\Serializers\BasicEntitySerializer;
use Railroad\Railtracker\Entities\Exception as ExceptionEntity;
use Railroad\Railtracker\Entities\Request;
use Railroad\Railtracker\Entities\RequestException;
use Railroad\Railtracker\Managers\RailtrackerEntityManager;
use Railroad\Railtracker\Services\BatchService;
use Railroad\Railtracker\ValueObjects\ExceptionVO;
use Railroad\Railtracker\ValueObjects\RequestVO;

class ExceptionTracker extends TrackerBase
{
    /**
     * @var RequestTracker
     */
    private $requestTracker;

    /*
     * Argument 5 passed to Railroad\Railtracker\Trackers\TrackerBase::__construct() must be an instance of
     * Railroad\Railtracker\Services\BatchService, instance of Illuminate\Cookie\CookieJar given, called in
     * /app/railtracker/src/Trackers/ExceptionTracker.php on line 49
     */
    public function __construct(
        DatabaseManager $databaseManager,
        Router $router,
        CookieJar $cookieJar,
        Repository $cache = null,
        BatchService $batchService,
        BasicEntitySerializer $basicEntitySerializer,
        RailtrackerEntityManager $entityManager,
        RequestTracker $requestTracker
    ){
        parent::__construct(
            $databaseManager,
            $router,
            $cookieJar,
            $cache,
            $batchService,
            $basicEntitySerializer,
            $entityManager
        );
        $this->requestTracker = $requestTracker;
        $this->batchService = $batchService;
    }

    /**
     * @param Exception $exception
     * @param $uuid
     * @return void
     */
    public function trackException(Exception $exception, $uuid)
    {
        if(!empty(RequestVO::$UUID)){
            try {

                $uuid = RequestVO::$UUID;

                $exceptionVO = new ExceptionVO($exception, $uuid);

                $this->batchService->storeException($exceptionVO);

            } catch (Exception $exception) {
                error_log($exception);
            }
        }
    }

    /**
     * @param $data
     * @return ExceptionEntity|RequestException
     * @throws Exception
     */
    public function hydrate($data)
    {
        $exceptionEntity = new ExceptionEntity();

        $exceptionEntity->setCode($data['code'] ?? null);
        $exceptionEntity->setLine($data['line'] ?? null);
        $exceptionEntity->setExceptionClass($data['exceptionClass'] ?? null);
        $exceptionEntity->setFile($data['file'] ?? null);
        $exceptionEntity->setMessage($data['message'] ?? null);
        $exceptionEntity->setTrace($data['trace'] ?? null);

        $requestException = new RequestException();

        $requestException->setException($exceptionEntity);
        $requestException->setCreatedAtTimestampMs($data['createdAtTimestampMs']);

        return $requestException;
    }
}
