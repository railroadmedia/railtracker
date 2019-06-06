<?php

namespace Railroad\Railtracker\Console\Commands;

use Exception;
use Illuminate\Support\Collection;
use Railroad\Railtracker\Entities\RequestAgent;
use Railroad\Railtracker\Entities\RequestDevice;
use Railroad\Railtracker\Entities\RequestLanguage;
use Railroad\Railtracker\Entities\RequestMethod;
use Railroad\Railtracker\Entities\Route;
use Railroad\Railtracker\Entities\Url;
use Railroad\Railtracker\Entities\UrlDomain;
use Railroad\Railtracker\Entities\UrlPath;
use Railroad\Railtracker\Entities\UrlProtocol;
use Railroad\Railtracker\Entities\UrlQuery;
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
            $valuesThisChunk = [];

            foreach ($keysThisChunk as $keyThisChunk) {
                $valuesThisChunk = array_merge(
                    $valuesThisChunk,
                    $this->batchService->cache()->smembers($keyThisChunk)
                );
            }

            // --------------------------------------------------------------------------------

            $valuesThisChunk = collect($valuesThisChunk);

            $entityTypes = [
                RequestAgent::class => $valuesThisChunk->pluck('xXx'),
                RequestDevice::class => $valuesThisChunk->pluck('xXx'),
                RequestLanguage::class => $valuesThisChunk->pluck('xXx'),
                RequestMethod::class => $valuesThisChunk->pluck('xXx'),
                Route::class => $valuesThisChunk->pluck('xXx'),
            ];

            foreach($entityTypes as $class => $data){
                $entitiesByHash = $this->keyByHash($data);
                $entities[] = $this->getExistingBulkInsertNew($class, $entitiesByHash);
            }

            // --------------------------------------------------------------------------------

            $urlValuesThisChunk = $valuesThisChunk->pluck('url');

            $urlEntityTypes =[
                UrlProtocol::class => $urlValuesThisChunk->pluck('xXx'), // todo: specify key
                UrlDomain::class => $urlValuesThisChunk->pluck('xXx'), // todo: specify key
                UrlPath::class => $urlValuesThisChunk->pluck('xXx'), // todo: specify key
                UrlQuery::class => $urlValuesThisChunk->pluck('xXx'), // todo: specify key
            ];

            // todo: what to do for URLs?
//            foreach($entityTypes as $class => $data){
//                $entitiesByHash = $this->keyByHash($data);
//                $entities[] = $this->getExistingBulkInsertNew($class, $entitiesByHash);
//            }

            // --------------------------------------------------------------------------------

            $refererUrlValuesThisChunk = $valuesThisChunk->pluck('referer-url');

            $refererUrlEntityTypes =[
                UrlProtocol::class => $refererUrlValuesThisChunk->pluck('xXx'), // todo: specify key
                UrlDomain::class => $refererUrlValuesThisChunk->pluck('xXx'), // todo: specify key
                UrlPath::class => $refererUrlValuesThisChunk->pluck('xXx'), // todo: specify key
                UrlQuery::class => $refererUrlValuesThisChunk->pluck('xXx'), // todo: specify key
            ];

            // todo: what to do for URLs?
//            foreach($entityTypes as $class => $data){
//                $entitiesByHash = $this->keyByHash($data);
//                $entities[] = $this->getExistingBulkInsertNew($class, $entitiesByHash);
//            }

            try{
                $this->entityManager->flush();
            }catch(\Exception $exception){
                error_log($exception);
            }

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

        foreach ($entitiesByHash as $agentHash => $agentByHash) {

            if (!isset($existingEntitiesByHash[$agentHash])) {

                $data = $entitiesByHash[$agentHash];

                try{
                    $entity = $this->processForType($class, $data);

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
     * @return RequestAgent
     * @throws Exception
     */
    private function processForType($class, $data)
    {
        $entity = new $class;
        $entity->setFromData($data);

//        switch($class){
//            case(RequestAgent::class):
//                $entity = new RequestAgent();
//                $entity->setName($data['name']);
//                $entity->setBrowserVersion($data['browserVersion']);
//                $entity->setBrowser($data['browser']);
//                break;
//            case(RequestDevice::class):
//                $entity = new RequestDevice();
//                // todo
//
//                $entity->setHash();
//                break;
//            case(RequestLanguage::class):
//                $entity = new RequestLanguage();
//                // todo
//
//                $entity->setHash();
//                break;
//            case(RequestMethod::class):
//                $entity = new RequestMethod();
//                // todo
//
//                $entity->setHash();
//                break;
//            case(Route::class):
//                $entity = new Route();
//                // todo
//
//                $entity->setHash();
//                break;
//            case(Url::class):
//                $entity = new Url();
//                // todo
//
//                $entity->setHash();
//                break;
//            case(UrlProtocol::class):
//                $entity = new UrlProtocol();
//                // todo
//
//                $entity->setHash();
//                break;
//            case(UrlDomain::class):
//                $entity = new UrlDomain();
//                // todo
//
//                $entity->setHash();
//                break;
//            case(UrlPath::class):
//                $entity = new UrlPath();
//                // todo
//
//                $entity->setHash();
//                break;
//            case(UrlQuery::class):
//                $entity = new UrlQuery();
//                // todo
//
//                $entity->setHash();
//                break;
//        }

        if(empty($entity)){
            throw new Exception('entity empty for data: ' . var_export($data, true));
        }

        $entity->setHash();
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
            $entitiesByHash[$data->getHash()] = $datum;
        }

        return $entitiesByHash;
    }
}
