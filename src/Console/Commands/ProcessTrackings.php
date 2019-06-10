<?php

namespace Railroad\Railtracker\Console\Commands;

use Exception;
use Illuminate\Support\Collection;
use Railroad\Railtracker\Entities\RailtrackerEntityInterface;
use Railroad\Railtracker\Entities\RequestAgent;
use Railroad\Railtracker\Entities\RequestDevice;
use Railroad\Railtracker\Entities\RequestLanguage;
use Railroad\Railtracker\Entities\RequestMethod;
use Railroad\Railtracker\Entities\Route;
use Railroad\Railtracker\Managers\RailtrackerEntityManager;
use Railroad\Railtracker\Services\BatchService;
use Railroad\Railtracker\Trackers\ExceptionTracker;
use Railroad\Railtracker\Trackers\RequestTracker;
use Railroad\Railtracker\Trackers\ResponseTracker;

class ProcessTrackings extends \Illuminate\Console\Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'ProcessTrackings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process items to track.';

    /**
     * @var BatchService
     */
    private $batchService;

    /**
     * @var RequestTracker
     */
    private $requestTracker;

    /**
     * @var ExceptionTracker
     */
    private $exceptionTracker;

    /**
     * @var ResponseTracker
     */
    private $responseTracker;

    /**
     * @var RailtrackerEntityManager
     */
    private $entityManager;

    /**
     * @var int
     */
    private $scanSize;

    /**
     * ProcessTrackings constructor.
     * @param BatchService $batchService
     * @param RequestTracker $requestTracker
     * @param ExceptionTracker $exceptionTracker
     * @param ResponseTracker $responseTracker
     * @param RailtrackerEntityManager $entityManager
     */
    public function __construct(
        BatchService $batchService,
        RequestTracker $requestTracker,
        ExceptionTracker $exceptionTracker,
        ResponseTracker $responseTracker,
        RailtrackerEntityManager $entityManager
    )
    {
        parent::__construct();

        $this->batchService = $batchService;
        $this->requestTracker = $requestTracker;
        $this->exceptionTracker = $exceptionTracker;
        $this->responseTracker = $responseTracker;
        $this->entityManager = $entityManager;
    }

    /**
     * return true
     */
    public function handle()
    {
        $redisIterator = null;

        while ($redisIterator !== 0) {

            // --------------------------------------------------------------------------------

            $requestKeys =
                $this->batchService
                    ->cache()
                    ->scan(
                        $redisIterator,
                        [
                            'MATCH' => $this->batchService->batchKeyPrefix . '*',
                            'COUNT' => config('railtracker.scan-size', 1000)
                        ]
                    );

            $redisIterator = (integer)$requestKeys[0];

            $keysThisChunk = $requestKeys[1];
            $valuesThisChunk = new Collection();

            foreach ($keysThisChunk as $keyThisChunk) {
                $values = $this->batchService->cache()->smembers($keyThisChunk);
                foreach($values as $value){
                    $valuesThisChunk->push(unserialize($value));
                }
            }


            // --------------------------------------------------------------------------------
            // ------------------------------ request processing ------------------------------
            // --------------------------------------------------------------------------------

            $requests = $valuesThisChunk->filter(function($candidate){
                return $candidate['type'] === 'request';
            });

            if(!empty($requests)){
                $mappedAgents = $requests->map(function($request){return $request[RequestAgent::$KEY];})->all();
                $mappedDevices = $requests->map(function($request){return $request[RequestDevice::$KEY];})->all();
                $mappedLanguages = $requests->map(function($request){return $request[RequestLanguage::$KEY];})->all();
                $mappedMethods = $requests->map(function($request){return $request[RequestMethod::$KEY];})->all();
                $mappedRoutes = $requests->map(function($request){return $request[Route::$KEY];})->all();

                $requestComponents = [
                    RequestAgent::class => $mappedAgents,
                    RequestDevice::class => $mappedDevices,
                    RequestLanguage::class => $mappedLanguages,
                    RequestMethod::class => $mappedMethods,
                    Route::class => $mappedRoutes,
                    // todo: url handling
                ];

                // todo: do above (getting $entityTypes) for URLs?

                try {
                    foreach($requestComponents as $class => $component){

                        // see stackoverflow.com/a/946300 for potential issues
                        $component = array_map("unserialize", array_unique(array_map("serialize", $component)));

                        $entitiesByHash = $this->keyByHash($dataForType); // this will also remove duplicates
                        $entities[$class] = $this->getExistingBulkInsertNew($class, $entitiesByHash);
                    }
                    $this->entityManager->flush();
                    $this->entityManager->clear();
                } catch (Exception $e) {
                    error_log($e);
                }
            }

            $stopHere = true;

            // --------------------------------------------------------------------------------


//            // todo: if above fails, still process below, or skip?
//
//            // --------------------------------------------------------------------------------
//
//            $urlValuesThisChunk = $valuesThisChunk->pluck('url');
//
//            $urlEntityTypes =[
//                UrlProtocol::class => $urlValuesThisChunk->pluck('xXx'), // todo: specify key
//                UrlDomain::class => $urlValuesThisChunk->pluck('xXx'), // todo: specify key
//                UrlPath::class => $urlValuesThisChunk->pluck('xXx'), // todo: specify key
//                UrlQuery::class => $urlValuesThisChunk->pluck('xXx'), // todo: specify key
//            ];
//
//            // todo: what to do for URLs?
////            foreach($entityTypes as $class => $data){
////                $entitiesByHash = $this->keyByHash($data);
////                $entities[] = $this->getExistingBulkInsertNew($class, $entitiesByHash);
////            }
//
//            // --------------------------------------------------------------------------------
//
//            $refererUrlValuesThisChunk = $valuesThisChunk->pluck('referer-url');
//
//            $refererUrlEntityTypes =[
//                UrlProtocol::class => $refererUrlValuesThisChunk->pluck('xXx'), // todo: specify key
//                UrlDomain::class => $refererUrlValuesThisChunk->pluck('xXx'), // todo: specify key
//                UrlPath::class => $refererUrlValuesThisChunk->pluck('xXx'), // todo: specify key
//                UrlQuery::class => $refererUrlValuesThisChunk->pluck('xXx'), // todo: specify key
//            ];
//
//            // todo: what to do for URLs?
////            foreach($entityTypes as $class => $data){
////                $entitiesByHash = $this->keyByHash($data);
////                $entities[] = $this->getExistingBulkInsertNew($class, $entitiesByHash);
////            }




        }

        // todo: clear this (some probably to re-incorporate into new structure, some probably to delete)
//        foreach (array_chunk($requestKeys, $chunkSize) as $requestKeysChunk) {
//
//            foreach ($requestKeysChunk as $requestKey) {
//
//                try {
//                    $requestData = unserialize(
//                        $this->batchService->cache()
//                            ->get($requestKey)
//                    );
//
//                    $uuid = $requestData['uuid'];
//
//                    $exceptionKey = $this->batchService->batchKeyPrefix . 'exception_' . $uuid;
//                    $responseKey = $this->batchService->batchKeyPrefix . 'response_' . $uuid;
//
//                    $exceptionData = unserialize(
//                        $this->batchService->cache()
//                            ->get($exceptionKey)
//                    );
//                    $responseData = unserialize(
//                        $this->batchService->cache()
//                            ->get($responseKey)
//                    );
//
//                    $requestEntity = $this->requestTracker->process($requestData);
//
//                    if (!empty($exceptionData)) {
//                        $this->exceptionTracker->process($exceptionData, $requestEntity);
//                    }
//
//                    $this->responseTracker->process($responseData, $requestEntity);
//
//                    $this->entityManager->clear();
//
//                } catch (Exception $exception) {
//                    error_log($exception);
//                    $errorsCount++;
//                }
//                $this->batchService->forget($requestKey);
//                $this->batchService->forget($exceptionKey ?? null);
//                $this->batchService->forget($responseKey ?? null);
//
//                $processedCount++;
//            }
//        }
//
//        $msg =
//            'Processed ' .
//            $processedCount .
//            ' requests (and their responses and sometimes exceptions). ' .
//            'No problems arose during processing';
//
//        if ($errorsCount > 0) {
//            $msg =
//                'Processed ' .
//                $processedCount .
//                ' requests (and their responses and sometimes exceptions). ' .
//                $errorsCount .
//                ' errors caught in ProcessTracking\'s try-catch.';
//        }
//
//        $this->info($msg);

        return true;
    }

    /**
     * @param string $class
     * @param array $entitiesByHash
     * @return array
     */
    private function getExistingBulkInsertNew($class, $entitiesByHash)
    {
        $qb = $this->entityManager->createQueryBuilder();

        /** @var RailtrackerEntityInterface[] $existingEntities */
        $existingEntities =
            $qb->select('a')
                ->from($class, 'a')
                ->where('a.hash IN (:hashes)')
                ->setParameter('hashes', array_keys($entitiesByHash))
                ->getQuery()
                ->getResult();

        // todo:create interface for entities, and they add docblock here with this var  as that interface
        $existingEntitiesByHash = [];

        foreach ($existingEntities as $existingEntity) {
            $existingEntitiesByHash[$existingEntity->getHash()] = $existingEntity;
        }

        $entities = [];

        foreach ($entitiesByHash as $hash => $entity) {

            if (!isset($existingEntitiesByHash[$hash])) {

//                /** @var RailtrackerEntity $classObj */
//                $classObj = new $class;
//                $key = $classObj::$KEY;

                try{
                    $entity = $this->processForType($class, $entity);

                    $entities[$entity->getHash()] = $entity;

                    $this->entityManager->persist($entity);

                }catch(Exception $exception){
                    error_log($exception);
                }
            }
        }
        return $entities;
    }

    /**
     * @param $class
     * @param $data
     * @return RailtrackerEntityInterface
     * @throws Exception
     */
    private function processForType($class, $data)
    {
        /** @var RailtrackerEntityInterface $entity */
        $entity = new $class;
        $entity->setFromData($data);
        $entity->setHash(); // todo: can|should this be put into "setFromData" methods. Probably *can*, probably *should NOT*.
        return $entity;
    }

    /**
     * @param $data
     * @return array
     */
    private function keyByHash($data)
    {
        $entitiesByHash = [];

        foreach($data as $datum){
            $entitiesByHash[$datum['hash']] = $datum;
        }

        return $entitiesByHash;
    }
}
