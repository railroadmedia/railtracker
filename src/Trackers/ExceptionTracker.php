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
        if(!empty(RequestTracker::$uuid)){
            try {
                $exceptionEntity = new ExceptionEntity();
                $exceptionEntity->setCode($exception->getCode());
                $exceptionEntity->setLine($exception->getLine());
                $exceptionEntity->setExceptionClass(get_class($exception));
                $exceptionEntity->setFile($exception->getFile());
                $exceptionEntity->setMessage($exception->getMessage());
                $exceptionEntity->setTrace($exception->getTraceAsString());
                $exceptionEntitySerialized = $this->serialize($exceptionEntity);

                $requestExceptionEntitySerialized = $this->trackRequestException(
                    $exceptionEntitySerialized
                );

                $this->batchService->addToBatch($requestExceptionEntitySerialized, 'exception', $uuid);

            } catch (Exception $exception) {
                error_log($exception);
            }
        }
    }

    /**
     * @param array $exceptionEntitySerialized
     * @return array
     */
    public function trackRequestException($exceptionEntitySerialized)
    {
        $requestExceptionEntity = new RequestException();
        $requestExceptionEntity->setCreatedAtTimestampMs(round(microtime(true) * 1000));
        $requestExceptionEntitySerialized = $this->serialize($requestExceptionEntity);
        $requestExceptionEntitySerialized['exception'] = $exceptionEntitySerialized;
        return $requestExceptionEntitySerialized;
    }

    /**
     * @param $data
     * @param Request $request
     * @return null |null
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function process($data, Request $request)
    {
        $hydratedExceptionOrRequestException = $this->hydrate($data);
        $hydratedExceptionOrRequestException->setRequest($request);

        $this->entityManager->persist($hydratedExceptionOrRequestException);
        $this->entityManager->flush();

        return $hydratedRequest ?? null;
    }

    /**
     * @param $data
     * @return ExceptionEntity|RequestException
     * @throws Exception
     */
    public function hydrate($data)
    {
        if($this->isRequestException($data)){
            return $this->hydrateRequestException($data);
        }

        return $this->hydrateException($data);
    }

    /**
     * @param $data
     * @return ExceptionEntity
     */
    private function hydrateException($data)
    {
        $exceptionEntity = new ExceptionEntity();

        $exceptionEntity->setCode($data['code'] ?? null);
        $exceptionEntity->setLine($data['line'] ?? null);
        $exceptionEntity->setExceptionClass($data['exceptionClass'] ?? null);
        $exceptionEntity->setFile($data['file'] ?? null);
        $exceptionEntity->setMessage($data['message'] ?? null);
        $exceptionEntity->setTrace($data['trace'] ?? null);

        return $exceptionEntity;
    }

    /**
     * @param $data
     * @return RequestException
     * @throws Exception
     */
    private function hydrateRequestException($data)
    {
        $requestException = new RequestException();

        $hydratedExceptionEntity = $this->hydrateException($data['exception']);

        $requestException->setException($hydratedExceptionEntity);
        $requestException->setCreatedAtTimestampMs($data['createdAtTimestampMs']);

        return $requestException;
    }

    /**
     * @param $data
     * @return bool
     */
    private function isRequestException($data)
    {
        $isException = false;
        $isRequestException = false;

        $keysInData = array_keys($data);

        /*
         * Note that this method currently depends on the two classes here not sharing any property names.
         */
        $exceptionEntityProperties = [
            'code',
            'line',
            'exceptionClass',
            'name',
            'message',
            'trace',
        ];
        $requestExceptionEntityProperties = [
            'exception',
            'request',
            'createdAtTimestampMs',
        ];

        foreach($keysInData as $key){

            $inArray = in_array($key, $exceptionEntityProperties);

            if($inArray){
                $isException = true;
            }
            if(in_array($key, $requestExceptionEntityProperties)){
                $isRequestException = true;
            }
        }

        $bothTrue = $isException && $isRequestException;
        $bothFalse = !$isException && !$isRequestException;

        if($bothTrue || $bothFalse){
            error_log(
                'error in isRequestException. Unable to determine entity type to use. Data: ' . var_export($data, true)
            );
        }

        return $isRequestException;
    }
}