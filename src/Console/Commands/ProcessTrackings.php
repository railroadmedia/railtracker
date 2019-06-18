<?php

namespace Railroad\Railtracker\Console\Commands;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Railroad\Railtracker\Entities\Exception as ExceptionEntity;
use Railroad\Railtracker\Entities\RailtrackerEntityInterface;
use Railroad\Railtracker\Entities\Request;
use Railroad\Railtracker\Entities\RequestAgent;
use Railroad\Railtracker\Entities\RequestDevice;
use Railroad\Railtracker\Entities\RequestException;
use Railroad\Railtracker\Entities\RequestLanguage;
use Railroad\Railtracker\Entities\RequestMethod;
use Railroad\Railtracker\Entities\Response;
use Railroad\Railtracker\Entities\ResponseStatusCode;
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
     * @var string
     */
    protected $name = 'ProcessTrackings';

    /**
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
    ){
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
        $counts = ['requests' => 0, 'reqExc' => 0, 'responses' => 0];

        while ($redisIterator !== 0) {

            $matchString = $this->batchService->batchKeyPrefix . '*';
            $batchSize = config('railtracker.scan-size', 1000);
            $criteria = ['MATCH' => $matchString,'COUNT' => $batchSize];

            $scanResult = $this->batchService->cache()->scan($redisIterator, $criteria);
            $redisIterator = (integer) $scanResult[0];
            $keys = $scanResult[1];

            if(!empty($keys)){
                $valuesThisChunk = $this->getValuesThisChunk($keys);

                $requests = $this->processRequests($valuesThisChunk);
                $requestExceptions = $this->processRequestExceptions($valuesThisChunk, $requests);
                $responses = $this->processResponses($valuesThisChunk, $requests);

                $this->batchService->forget($keys);

                $counts['requests'] = ($counts['requests'] ?? 0) + $requests->count();
                $counts['reqExc'] = ($counts['reqExc'] ?? 0) + $requestExceptions->count();
                $counts['responses'] = ($counts['responses'] ?? 0) + $responses->count();
            }
        }

        $output = 'Processed ' .
            $counts['requests'] . ' ' . ($counts['requests'] === 1 ? 'request' : 'requests') . ', ' .
            $counts['reqExc'] . ' ' . ($counts['reqExc'] === 1 ? 'requestException' : 'requestExceptions') . ', and ' .
            $counts['responses'] . ' ' . ($counts['responses'] === 1 ? 'response' : 'responses') . '.';

        $this->info($output);

        try {
            $this->entityManager->clear();
        } catch (Exception $e) {
            error_log($e);
        }

        return true;
    }

    /**
     * @param Collection $mappedUrls
     * @param $entities
     * @return array
     */
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

                if(empty($url[$key])) {
                    continue;
                }
                $hash = $url[$key]['hash'];

                foreach ($entities as $type => $data) {
                    if (in_array($type, $typesToSearch) && isset($data[$hash])) {
                        $entityToAttach = $data[$hash];
                        $url[$key] = $entityToAttach;
                    }
                }
            }
            return $url;
        })->all();
    }

    /**
     * @param string $class
     * @param array $arraysByHash
     * @return array
     */
    private function getExistingBulkInsertNew($class, $arraysByHash)
    {
        $existingEntitiesByHash = $this->getPreExistingFromSet($class, $arraysByHash);

        $entities = $this->getRecordsOfTypeCreateNewAsNeeded($class, $arraysByHash, $existingEntitiesByHash);

        return $entities;
    }

    /**
     * @param $class
     * @param $arraysByHash
     * @param array $existingEntitiesByHash
     * @return array
     */
    private function getRecordsOfTypeCreateNewAsNeeded($class, $arraysByHash, $existingEntitiesByHash = [])
    {
        $entities = [];

        foreach ($arraysByHash as $hash => $entity) {

            if (isset($existingEntitiesByHash[$hash])) {
                $entities[$hash] = $existingEntitiesByHash[$hash];
            }else{
                try{
                    $entity = $this->processForType($class, $entity);

                    if($entity->allValuesAreEmpty()){
                        continue;
                    }

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
        $entity->setHash();
        return $entity;
    }

    /**
     * @param $data
     * @return array
     */
    private function keyByHash($data)
    {
        foreach($data as $datum){
            if(isset($datum['hash'])){
                $arraysByHash[$datum['hash']] = $datum;
            }
        }

        return $arraysByHash ?? [];
    }

    /**
     * @param Collection $items
     * @param $keyToMap
     * @return array
     */
    private function getForTypeAndKeyByHash(Collection $items, $keyToMap){
        $mappedEntities = $items->map(
            function($item) use ($keyToMap){
                return $item[$keyToMap];
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

    /**
     * @param Collection $requests
     * @param $entities
     * @return array
     */
    private function createRequestEntitiesAndAttachAssociatedEntities(Collection $requests, $entities)
    {
        $associationsClassesAndKeys = [
            RequestAgent::$KEY => RequestAgent::class,
            RequestDevice::$KEY => RequestDevice::class,
            RequestLanguage::$KEY => RequestLanguage::class,
            RequestMethod::$KEY => RequestMethod::class,
            Route::$KEY => Route::class,
            Url::$KEY => Url::class,
            Url::$REFERER_URL_KEY => Url::class,
        ];

        foreach($requests as &$requestData){
            foreach($associationsClassesAndKeys as $key => $class)
            {
                $hashRequired = $requestData[$key]['hash'];
                $candidates = $entities[$class];

                if(isset($candidates[$hashRequired])) {
                    $requestData[$key] = $candidates[$hashRequired];
                }
            }

            try {
                $r = new Request();
                $r->setUuid($requestData['uuid']);
                $r->setUserId($requestData['userId']);
                $r->setCookieId($requestData['cookieId']);
                $r->setGeoip($requestData['geoip']);
                $r->setClientIp($requestData['clientIp']);
                $r->setIsRobot($requestData['isRobot']);
                $r->setRequestedOn(Carbon::parse($requestData['requestedOn']));
                $r->setAgent($requestData['agent']);
                $r->setDevice($requestData['device']);
                $r->setLanguage($requestData['language']);
                $r->setMethod($requestData['method']);

                /*
                 * url_id, referer_url_id, and route_id columns of table are nullable, thus only set if entity available
                 * here. We have to check for object of type because if object is not set an array will be, but trying
                 * to set this will cause an error.
                 */
                if(is_a($requestData['url'], Url::class)){
                    $r->setUrl($requestData['url']);
                }
                if(is_a($requestData['refererUrl'], Url::class)){
                    $r->setRefererUrl($requestData['refererUrl']);
                }
                if(is_a($requestData['route'], Route::class)){
                    $r->setRoute($requestData['route']);
                }

                $this->entityManager->persist($r);

                $requestEntitiesByUuid[$r->getUuid()] = $r ?? null; // todo: why this ?? operator? Delete if shouldn't be here

            } catch (Exception $e) {
                error_log($e);
            }
        }

        try{
            $this->entityManager->flush();
        } catch (Exception $e) {
            error_log($e);
        }

        return $requestEntitiesByUuid ?? [];
    }

    /**
     * @param string $class
     * @param array $arraysByHash
     * @return array
     */
    private function getPreExistingFromSet($class, $arraysByHash)
    {
        $qb = $this->entityManager->createQueryBuilder();

        /** @var RailtrackerEntityInterface[] $existingEntities */
        $existingEntities =
            $qb->select('a')
                ->from($class, 'a')
                ->where('a.hash IN (:whereValues)')
                ->setParameter('whereValues', array_keys($arraysByHash))
                ->getQuery()
                ->getResult();

        $existingEntitiesByHash = [];

        // key by hash
        foreach ($existingEntities as $existingEntity) {
            $existingEntitiesByHash[$existingEntity->getHash()] = $existingEntity;
        }

        return $existingEntitiesByHash;
    }

    /**
     * @param $valuesThisChunk
     * @return array|Collection
     */
    private function processRequests(Collection $valuesThisChunk)
    {
        $entities = [];

        $requests = $valuesThisChunk->filter(function($candidate){
            return $candidate['type'] === 'request';
        });

        if(!empty($requests)){

            // ---------------------------------------------------------------------------------------------------------
            // part 1 of 6 - simple associations -----------------------------------------------------------------------
            // ---------------------------------------------------------------------------------------------------------

            $mappedAgents = $this->getForTypeAndKeyByHash($requests, RequestAgent::$KEY);
            $mappedDevices = $this->getForTypeAndKeyByHash($requests, RequestDevice::$KEY);
            $mappedLanguages = $this->getForTypeAndKeyByHash($requests, RequestLanguage::$KEY);
            $mappedMethods = $this->getForTypeAndKeyByHash($requests, RequestMethod::$KEY);
            $mappedRoutes = $this->getForTypeAndKeyByHash($requests, Route::$KEY);

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
            } catch (Exception $e) {
                error_log($e);
            }

            // ---------------------------------------------------------------------------------------------------------
            // part 2 of 6 - associations of urls ----------------------------------------------------------------------
            // ---------------------------------------------------------------------------------------------------------

            $mappedUrls = $this->getAndMapUrlsFromRequests($requests);

            // protocol and domain are *not* nullable

            $mappedUrlProtocols = $this->getForTypeAndKeyByHash($mappedUrls, UrlProtocol::$KEY);

            $mappedUrlDomains = $this->getForTypeAndKeyByHash($mappedUrls, UrlDomain::$KEY);

            // path and query *are* nullable

            $urlsWithPaths = $this->filterForSetEntitiesOfAType($mappedUrls, UrlPath::$KEY);
            $mappedUrlPaths = $this->getForTypeAndKeyByHash($urlsWithPaths, UrlPath::$KEY);

            $urlsWithQueries = $this->filterForSetEntitiesOfAType($mappedUrls, UrlQuery::$KEY);
            $mappedUrlQueries = $this->getForTypeAndKeyByHash($urlsWithQueries, UrlQuery::$KEY);

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

            } catch (Exception $e) {
                error_log($e);
            }

            // ---------------------------------------------------------------------------------------------------------
            // part 3 of 6 - attaching children to urls and then processing those --------------------------------------
            // ---------------------------------------------------------------------------------------------------------

            $mappedUrls = $this->mapChildrenToUrls($mappedUrls, $entities);

            try{
                $entities[Url::class] = $this->getExistingBulkInsertNew(Url::class, $mappedUrls);

                $this->entityManager->flush();

            } catch (Exception $e) {
                error_log($e);
            }

            // ---------------------------------------------------------------------------------------------------------
            // part 4 of 6 - get previous before adding new ------------------------------------------------------------
            // ---------------------------------------------------------------------------------------------------------

            /*
             * get previous before adding new, otherwise result will contain new, and to get the "previous" you'd have
             * to skip the first result.
             */

            foreach($requests as $request){
                if ($request['userId'] !== null) {
                    $previousRequestsDatabaseRows = $this->requestTracker->getPreviousRequestsDatabaseRows($request);
                }
            }

            // ---------------------------------------------------------------------------------------------------------
            // part 5 of 6 - insert requests ---------------------------------------------------------------------------
            // ---------------------------------------------------------------------------------------------------------

            /*
             * every association of the request (everything that itself is an entity should already have
             * something for it in the $entities. This method doesn't evaluate and fill for missing associations.
             */
            $requestEntities = collect($this->createRequestEntitiesAndAttachAssociatedEntities($requests, $entities));

            // ---------------------------------------------------------------------------------------------------------
            // part 6 of 6 - misc hooks --------------------------------------------------------------------------------
            // ---------------------------------------------------------------------------------------------------------

            $this->requestTracker->fireRequestTrackedEvents($requestEntities, $previousRequestsDatabaseRows ?? []);

            $this->requestTracker->updateUsersAnonymousRequests($requestEntities);
        }

        return $requestEntities ?? collect([]);
    }

    /**
     * @param $keysThisChunk
     * @return Collection
     */
    private function getValuesThisChunk($keysThisChunk)
    {
        $valuesThisChunk = new Collection();

        foreach ($keysThisChunk as $keyThisChunk) {
            $values = $this->batchService->cache()->smembers($keyThisChunk);
            foreach($values as $value){
                $valuesThisChunk->push(unserialize($value));
            }
        }

        return $valuesThisChunk;
    }

    /**
     * @param Collection $valuesThisChunk
     * @param Collection $requests
     * @return Collection
     */
    private function processResponses(Collection $valuesThisChunk, Collection $requests)
    {
        $responses = $this->getResponses($valuesThisChunk);

        if(empty($responses)){
            return collect([]);
        }

        $statusCodes = $this->getOrCreateResponseAssociatedEntities($responses);

        $responseEntities = $this->hydrateAndPersistResponses($responses, $requests, $statusCodes);

        return $responseEntities;
    }

    /**
     * @param $valuesThisChunk
     * @return mixed
     */
    private function getResponses($valuesThisChunk)
    {
        return $valuesThisChunk->filter(function($candidate){
            return $candidate['type'] === 'response';
        });
    }

    /**
     * @param $responses
     * @param $requests
     * @param $statusCodes
     * @return Collection
     */
    private function hydrateAndPersistResponses($responses, $requests, $statusCodes)
    {
        foreach($responses as $responseData) {

            $responseEntity = new Response();

            // simple|scalar values

            $responseEntity->setRespondedOn($responseData['respondedOn']);
            $responseEntity->setResponseDurationMs($responseData['responseDurationMs']);

            // association one: request

            $uuid = $responseData['uuid'];
            $requestToUse = $requests[$uuid] ?? null;

            if (!$requestToUse) {
                // todo: what to do here? Not need to assume it's present? send an Exception up level or something? Maybe remove the need for this error log? ... and|or make sure this is all handled and set up properly
                error_log('No matching request found');
            }

            $responseEntity->setRequest($requestToUse);

            // association two: status_code

            $statusCodeToUse = null;

            $statusCodeHashToFind = $responseData['status_code']['hash'] ?? null;

            foreach ($statusCodes as $candidateHash => $candidate) {
                if ($statusCodeHashToFind === $candidateHash) {
                    $statusCodeToUse = $candidate;
                }
            }

            $responseEntity->setStatusCode($statusCodeToUse);

            // persist

            try {
                $this->entityManager->persist($responseEntity);
            } catch (Exception $exception) {
                logger($exception); // todo: what to do here? What level of log to use? Alert? Error?
            }
            $responseEntities[] = $responseEntity;
        }

        try{
            $this->entityManager->flush();
        }catch(Exception $e){
            error_log($e);
        }

        return collect($responseEntities ?? []);
    }

    /**
     * @param $responses
     * @return array
     */
    private function getOrCreateResponseAssociatedEntities($responses)
    {
        $mappedStatusCodes = $this->getForTypeAndKeyByHash($responses, ResponseStatusCode::$KEY);

        try {
            $entities = $this->getExistingBulkInsertNew(ResponseStatusCode::class, $mappedStatusCodes);
            $this->entityManager->flush();
        } catch (Exception $e) {
            error_log($e);
        }

        return $entities ?? [];
    }
    /**
     * @param Collection $valuesThisChunk
     * @param Collection $requests
     * @return Collection
     */
    private function processRequestExceptions(Collection $valuesThisChunk, Collection $requests)
    {
        $entities = [];

        $requestExceptionsData = $valuesThisChunk->filter(function($candidate){
            return $candidate['type'] === 'request-exception';
        });

        if(!empty($requestExceptionsData)){

            $exceptions = $this->getForTypeAndKeyByHash($requestExceptionsData, 'exception');

            try{
                $entities[ExceptionEntity::class] =
                    $this->getExistingBulkInsertNew(ExceptionEntity::class, $exceptions);

                $this->entityManager->flush();
            } catch (Exception $e) {
                error_log($e);
            }

            // Map exceptions To RequestExceptions

            $exceptionsToMapToRequestExceptions = $entities[ExceptionEntity::class];

            $requestExceptionsData = $requestExceptionsData->map(
                function($singleRequestException) use ($exceptionsToMapToRequestExceptions)
                {
                    $targetHash = $singleRequestException['exception']['hash'];

                    $exceptionToAttach = $exceptionsToMapToRequestExceptions[$targetHash] ?? null;

                    if($exceptionToAttach) {
                        $singleRequestException['exception'] = $exceptionToAttach;
                    }

                    return $singleRequestException;
                }
            );

            // ---------------------------------------------------------------------------------------------------------

            // moved from "$requestExceptions = $this->hydrateRequestExceptions($requestExceptionsData, $requests);"

            $requestExceptionsMatchedWithRequestsKeyedByUuid = $this->matchWithRequest($requestExceptionsData, $requests);

            foreach($requestExceptionsMatchedWithRequestsKeyedByUuid as $set){

                $requestExceptionData = $set['request-exception'];
                $request = $set['request'];

                $exceptionData = $requestExceptionData['exception'];

                $exception = $exceptionData;

                if(!is_a($exceptionData, ExceptionEntity::class)){
                    error_log('Exception entity not set here, and this should not be possible.');
                } // todo: remove this, ideally by addressing the insecurity that leads you to believe it necessary.

                $requestException = new RequestException();
                $requestException->setRequest($request);
                $requestException->setException($exception);
                $requestException->setCreatedAtTimestampMs($requestExceptionData['createdAtTimestampMs']);

                $requestExceptions[] = $requestException;
            }

            $requestExceptions = collect($requestExceptions ?? []);

            // ---------------------------------------------------------------------------------------------------------

            foreach($requestExceptions as $requestException){
                try{
                    $this->entityManager->persist($requestException);
                }catch(Exception $exception){
                    error_log($exception);
                }
            }

            try{
                $this->entityManager->flush();
            }catch(Exception $exception){
                error_log($exception);
            }
        }

        return $requestExceptions ?? collect([]);
    }

    /**
     * @param Collection|array[] $requestExceptions
     * @param Collection|Request[] $requests
     * @return array
     */
    private function matchWithRequest(Collection $requestExceptions, Collection $requests)
    {
        foreach($requestExceptions as $requestException){

            $requestExceptionUuid = $requestException['uuid'];

            $matchingRequest = $requests->filter(function($request) use ($requestExceptionUuid){
                /** @var Request $request */
                $requestUuid = $request->getUuid();
                return $requestUuid === $requestExceptionUuid;
            })->first();

            $matched[$requestExceptionUuid] = [
                'request' => $matchingRequest,
                'request-exception' => $requestException,
            ];
        }

        return $matched ?? [];
    }
}
