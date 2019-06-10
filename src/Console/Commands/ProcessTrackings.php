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

    private function findInEntitiesToAttach($entities, $hash, $typesToSearch)
    {
        foreach($entities as $type => $data){
            if(!in_array($type, $typesToSearch)){
                continue;
            }
            if(isset($data[$hash])){
                return $data[$hash];
            }
        }
        return null;
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

                // request processing part 1 of 3 ------------------------------------------------

                $mappedAgents = $this->keyByHash($requests->map(function($request){return $request[RequestAgent::$KEY];})->all());
                $mappedDevices = $this->keyByHash($requests->map(function($request){return $request[RequestDevice::$KEY];})->all());
                $mappedLanguages = $this->keyByHash($requests->map(function($request){return $request[RequestLanguage::$KEY];})->all());
                $mappedMethods = $this->keyByHash($requests->map(function($request){return $request[RequestMethod::$KEY];})->all());
                $mappedRoutes = $this->keyByHash($requests->map(function($request){return $request[Route::$KEY];})->all());

                try {
                    $entities[RequestAgent::class] = $this->getExistingBulkInsertNew(RequestAgent::class, $mappedAgents);
                    $entities[RequestDevice::class] = $this->getExistingBulkInsertNew(RequestDevice::class, $mappedDevices);
                    $entities[RequestLanguage::class] = $this->getExistingBulkInsertNew(RequestLanguage::class, $mappedLanguages);
                    $entities[RequestMethod::class] = $this->getExistingBulkInsertNew(RequestMethod::class, $mappedMethods);
                    $entities[Route::class] = $this->getExistingBulkInsertNew(Route::class, $mappedRoutes);

                    $this->entityManager->flush();
                    $this->entityManager->clear();
                } catch (Exception $e) {
                    error_log($e);
                }

                // todo: if above fails, still process below, or skip?

                // request processing part 2 of 3 ------------------------------------------------

                $mappedUrls = collect(array_merge(
                    $requests->map(function($request){return $request[Url::$KEY];})->all(),
                    $requests->map(function($request){return $request[Url::$REFERER_URL_KEY];})->all()
                ));

                $mappedUrls = collect($this->keyByHash($mappedUrls));

                $mappedUrlProtocols = $this->keyByHash($mappedUrls->map(function($url){return $url[UrlProtocol::$KEY];})->all());
                $mappedUrlDomains = $this->keyByHash($mappedUrls->map(function($url){return $url[UrlDomain::$KEY];})->all());

                $mappedUrlPaths = collect($mappedUrls->filter(function($url){
                    return !empty($url[UrlPath::$KEY]);
                })->all());
                $mappedUrlPaths = $this->keyByHash($mappedUrlPaths->map(function($url){
                        return $url[UrlPath::$KEY];
                })->all());

                $mappedUrlQueries = collect($mappedUrls->filter(function($url){
                    return !empty($url[UrlQuery::$KEY]);
                })->all());
                $mappedUrlQueries = $this->keyByHash($mappedUrlQueries->map(function($url){
                        return $url[UrlQuery::$KEY];
                })->all());

                try{
                    $entities[UrlProtocol::class] = $this->getExistingBulkInsertNew(UrlProtocol::class, $mappedUrlProtocols);
                    $entities[UrlDomain::class] = $this->getExistingBulkInsertNew(UrlDomain::class, $mappedUrlDomains);
                    if(!empty($mappedUrlPaths)){
                        $entities[UrlPath::class] = $this->getExistingBulkInsertNew(UrlPath::class, $mappedUrlPaths);
                    }
                    if(!empty($mappedUrlQueries)){
                        $entities[UrlQuery::class] = $this->getExistingBulkInsertNew(UrlQuery::class, $mappedUrlQueries);
                    }

                    $this->entityManager->flush();
                    $this->entityManager->clear();
                } catch (Exception $e) {
                    error_log($e);
                }

                // request processing part 3 of 3 ------------------------------------------------

                // todo: attached entities
                $mappedUrls = $mappedUrls->map(function($url) use ($entities){

                    $typesToSearch = [UrlProtocol::class, UrlDomain::class, UrlPath::class, UrlQuery::class];

                    // protocol
                    $url['protocol'] = $url['protocol'] ?? null;
                    if($url['protocol']){
                        $hash = $url['protocol']['hash'];
                        $url['protocol'] = $this->findInEntitiesToAttach($entities, $hash, $typesToSearch);
                    }

                    // domain
                    $url['domain'] = $url['domain'] ?? null;
                    if($url['domain']){
                        $hash = $url['domain']['hash'];
                        $url['domain'] = $this->findInEntitiesToAttach($entities, $hash, $typesToSearch);
                    }

                    // path
                    $url['path'] = $url['path'] ?? null;
                    if($url['path']){
                        $hash = $url['path']['hash'];
                        $url['path'] = $this->findInEntitiesToAttach($entities, $hash, $typesToSearch);
                    }

                    // query
                    $url['query'] = $url['query'] ?? null;
                    if($url['query']){
                        $hash = $url['query']['hash'];
                        $url['query'] = $this->findInEntitiesToAttach($entities, $hash, $typesToSearch);
                    }

                    return $url;
                })->all();

                try{
                    $entities[Url::class] = $this->getExistingBulkInsertNew(Url::class, $mappedUrls);

                    $this->entityManager->flush();
                    $this->entityManager->clear();
                } catch (Exception $e) {
                    error_log($e);
                }

                $stopHere = true;
                $stopHere = true;
                $stopHere = true;

                // todo: process urls themselves.
//                foreach($mappedUrls as $url){
//
//                    //$processedUrls = $this->getExistingBulkInsertNew(Url::class, $mappedUrls);
//                }

                /*
                 * What to do here? attach child-entities by way of hash?
                 *
                 * Might have to totally rewrite process methods?
                 *
                 * It'll be the same dynamic as with the Urls and their child-entities.
                 */

                // $requestEntity = $this->requestTracker->process($requestData);
                // if (!empty($exceptionData)) {
                //     $this->exceptionTracker->process($exceptionData, $requestEntity);
                // }
                // $this->responseTracker->process($responseData, $requestEntity);
                // $this->entityManager->clear();

            }
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
            if (!isset($existingEntitiesByHash[$hash])) {
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
}
