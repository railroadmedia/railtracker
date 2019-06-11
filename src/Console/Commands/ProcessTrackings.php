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

    private function mapChildrenToUrls(Collection $mappedUrls, $entities)
    {
        return $mappedUrls->map(function($url) use ($entities)
        {
            $typesToSearch = [
                UrlProtocol::class,
                UrlDomain::class,
                UrlPath::class,
                UrlQuery::class
            ];

            $keys = [
                'protocol',
                'domain',
                'path',
                'query'
            ];

            /** @var $url Url */
            foreach($keys as $key){

                if(!empty($url[$key])){

                    $hash = $url[$key]['hash'];

                    foreach ($entities as $type => $data) {

                        if (in_array($type, $typesToSearch)) {

                            if (isset($data[$hash])) {

                                $entityToAttach = $data[$hash];

                                $url[$key] = $entityToAttach;

//                                $entityToAttach = $data[$hash];
//
//                                switch($type){
//                                    case UrlProtocol::class:
//                                        $url->setProtocol($entityToAttach);
//                                        break;
//                                    case UrlDomain::class:
//                                        $url->setDomain($entityToAttach);
//                                        break;
//                                    case UrlPath::class:
//                                        $url->setPath($entityToAttach);
//                                        break;
//                                    case UrlQuery::class:
//                                        $url->setQuery($entityToAttach);
//                                        break;
                            }
                        }
                    }
//                }else{
//                    $url[$key] = null;
                }
            }
            return $url;
        })->all();
    }

    /**
     * return true
     */
    public function handle()
    {
        $redisIterator = null;

        while ($redisIterator !== 0) {

            $entities = [];

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

                // request processing part 1 of 3 - simple associations or requests

                $mappedAgents = $this->mapForKeyAndKeyByHash($requests, RequestAgent::$KEY);
                $mappedDevices = $this->mapForKeyAndKeyByHash($requests, RequestDevice::$KEY);
                $mappedLanguages = $this->mapForKeyAndKeyByHash($requests, RequestLanguage::$KEY);
                $mappedMethods = $this->mapForKeyAndKeyByHash($requests, RequestMethod::$KEY);
                $mappedRoutes = $this->mapForKeyAndKeyByHash($requests, Route::$KEY);

                try {
                    $entities[RequestAgent::class] =
                        $this->getExistingBulkInsertNew(RequestAgent::class, $mappedAgents);

                    $entities[RequestDevice::class] =
                        $this->getExistingBulkInsertNew(RequestDevice::class, $mappedDevices);

                    $entities[RequestLanguage::class] =
                        $this->getExistingBulkInsertNew(RequestLanguage::class, $mappedLanguages);

                    $entities[RequestMethod::class] =
                        $this->getExistingBulkInsertNew(RequestMethod::class, $mappedMethods);

                    $entities[Route::class] =
                        $this->getExistingBulkInsertNew(Route::class, $mappedRoutes);

                    $this->entityManager->flush();
                    $this->entityManager->clear();
                } catch (Exception $e) {
                    error_log($e);
                }

                // request processing part 2 of 3 - associations of urls

                $mappedUrls = $this->getAndMapUrlsFromRequests($requests);

                // protocol and domain are *not* nullable

                $mappedUrlProtocols = $this->mapForKeyAndKeyByHash($mappedUrls, UrlProtocol::$KEY);

                $mappedUrlDomains = $this->mapForKeyAndKeyByHash($mappedUrls, UrlDomain::$KEY);

                // path and query *are* nullable

                $urlsWithPaths = $this->filterForSetEntitiesOfAType($mappedUrls, UrlPath::$KEY);
                $mappedUrlPaths = $this->mapForKeyAndKeyByHash($urlsWithPaths, UrlPath::$KEY);

                $urlsWithQueries = $this->filterForSetEntitiesOfAType($mappedUrls, UrlQuery::$KEY);
                $mappedUrlQueries = $this->mapForKeyAndKeyByHash($urlsWithQueries, UrlQuery::$KEY);

                try{
                    $entities[UrlProtocol::class] =
                        $this->getExistingBulkInsertNew(UrlProtocol::class, $mappedUrlProtocols);

                    $entities[UrlDomain::class] =
                        $this->getExistingBulkInsertNew(UrlDomain::class, $mappedUrlDomains);

                    $entities[UrlPath::class] =
                        $this->getExistingBulkInsertNew(UrlPath::class, $mappedUrlPaths);

                    $entities[UrlQuery::class] =
                        $this->getExistingBulkInsertNew(UrlQuery::class, $mappedUrlQueries);

                    $this->entityManager->flush();

                    /*
                     * Need to *not* call "entityManager->clear()" because otherwise EM will no longer track entities
                     * that later attach to URLs and it will then try to create those entities again as required
                     * cascade:persist
                     */

                    // $this->entityManager->clear();

                } catch (Exception $e) {
                    error_log($e);
                }

                // request processing part 3 of 3 ------------------------------------------------

                $mappedUrls = $this->mapChildrenToUrls($mappedUrls, $entities);

                try{
                    $entities[Url::class] = $this->getExistingBulkInsertNew(Url::class, $mappedUrls);

                    $this->entityManager->flush();
                    $this->entityManager->clear();
                } catch (Exception $e) {
                    error_log($e);
                }
            }


            // --------------------------------------------------------------------------------
            // ----------------------------- exception processing -----------------------------
            // --------------------------------------------------------------------------------




            // --------------------------------------------------------------------------------
            // ----------------------------- response processing ------------------------------
            // --------------------------------------------------------------------------------



        }

        // todo: clear|delete keys?

        // todo: print info about success|failure

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

        $existingEntitiesByHash = [];

        foreach ($existingEntities as $existingEntity) {
            $existingEntitiesByHash[$existingEntity->getHash()] = $existingEntity;
        }

        $entities = [];

        foreach ($entitiesByHash as $hash => $entity) {

            if (isset($existingEntitiesByHash[$hash])) {
                $entities[$hash] = $existingEntitiesByHash[$hash];
            }else{
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
        foreach($data as $datum){
            $entitiesByHash[$datum['hash']] = $datum;
        }

        return $entitiesByHash ?? [];
    }

    /**
     * @param Collection $urls
     * @param $keyToMap
     * @return array
     */
    private function mapForKeyAndKeyByHash(Collection $urls, $keyToMap){
        $mappedEntities = $urls->map(
            function($url) use ($keyToMap){
                return $url[$keyToMap];
            }
        )->all();
        return $this->keyByHash($mappedEntities);
    }

    /**
     * @param Collection $urls
     * @param $keyForRequired
     * @return Collection
     */
    private function filterForSetEntitiesOfAType(Collection $urls, $keyForRequired)
    {
        $existant = $urls->filter(function($url) use ($keyForRequired){
            return !empty($url[$keyForRequired]);
        })->all();

        return collect($existant);
    }

    /**
     * @param Collection $requests
     * @return Collection
     */
    private function getAndMapUrlsFromRequests(Collection $requests)
    {
        $urlsNotKeyed = collect(array_merge(
            $requests->map(function($request){return $request[Url::$KEY];})->all(),
            $requests->map(function($request){return $request[Url::$REFERER_URL_KEY];})->all()
        ));

        return collect($this->keyByHash($urlsNotKeyed));
    }
}
