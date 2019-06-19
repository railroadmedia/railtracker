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
     * @var Collection
     */
    private $valuesThisChunk;

    /**
     * @var Collection
     */
    private $requestsThisChunk;

    /**
     * @var Collection
     */
    private $requestExceptionsThisChunk;

    /**
     * @var Collection
     */
    private $responsesThisChunk;

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
                $this->determineValuesThisChunk($keys);

                $this->processRequests();
                $this->processRequestExceptions();
                $this->processResponses();

                $this->batchService->forget($keys);

                $counts['requests'] = ($counts['requests'] ?? 0) + $this->requestsThisChunk->count();
                $counts['reqExc'] = ($counts['reqExc'] ?? 0) + $this->requestExceptionsThisChunk->count();
                $counts['responses'] = ($counts['responses'] ?? 0) + $this->responsesThisChunk->count();
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
        /*
         * every association of the request (everything that itself is an entity) should already have
         * something for it in the $entities. This method doesn't evaluate and fill for missing associations.
         */

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
     * @param $requests
     * @return array
     */
    private function simpleAssociationsForRequests($requests)
    {
        $entities = [];

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

        return $entities;
    }

    /**
     * @param array $entities
     * @param Collection $mappedUrls
     * @return array
     */
    private function urlAssociationsForRequests($entities, Collection $mappedUrls)
    {
        // -------------------------------------------------------------------------------------------------------------
        // ---------- 1. protocol and domain are *not* nullable --------------------------------------------------------
        // -------------------------------------------------------------------------------------------------------------

        $mappedUrlProtocols = $this->getForTypeAndKeyByHash($mappedUrls, UrlProtocol::$KEY);

        $mappedUrlDomains = $this->getForTypeAndKeyByHash($mappedUrls, UrlDomain::$KEY);

        // -------------------------------------------------------------------------------------------------------------
        // ---------- 2. path and query *are* nullable -----------------------------------------------------------------
        // -------------------------------------------------------------------------------------------------------------

        $urlsWithPaths = $this->filterForSetEntitiesOfAType($mappedUrls, UrlPath::$KEY);
        $mappedUrlPaths = $this->getForTypeAndKeyByHash($urlsWithPaths, UrlPath::$KEY);

        $urlsWithQueries = $this->filterForSetEntitiesOfAType($mappedUrls, UrlQuery::$KEY);
        $mappedUrlQueries = $this->getForTypeAndKeyByHash($urlsWithQueries, UrlQuery::$KEY);

        // -------------------------------------------------------------------------------------------------------------
        // ---------- 3. get existing bulk insert new ------------------------------------------------------------------
        // -------------------------------------------------------------------------------------------------------------

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

        return $entities;
    }

    /**
     * @return void
     */
    private function processRequests()
    {
        $requests = $this->valuesThisChunk->filter(function($candidate){
            return $candidate['type'] === 'request';
        });

        if($requests->isEmpty()) {
            $this->requestsThisChunk = collect([]);
            return;
        }

        $entities = $this->simpleAssociationsForRequests($requests);

        $mappedUrls = $this->getAndMapUrlsFromRequests($requests);
        $entities = $this->urlAssociationsForRequests($entities, $mappedUrls);

        $mappedUrls = $this->mapChildrenToUrls($mappedUrls, $entities);
        try{
            $entities[Url::class] = $this->getExistingBulkInsertNew(Url::class, $mappedUrls);
            $this->entityManager->flush();
        } catch (Exception $e) {
            error_log($e);
        }

        // get previous before adding new, otherwise result will contain new
        foreach($requests as $request){
            if ($request['userId'] !== null) {
                $previousRequestsDatabaseRows[] = $this->requestTracker->getPreviousRequestsDatabaseRows($request);
            }
        }

        $requestEntities = collect($this->createRequestEntitiesAndAttachAssociatedEntities($requests, $entities));

        $this->requestTracker->updateUsersAnonymousRequests($requestEntities);

        // getAfter,compare difference
        foreach($requests as $request){
            if ($request['userId'] !== null) {
                $previousRequestsDatabaseRows_2[] = $this->requestTracker->getPreviousRequestsDatabaseRows($request);
            }
        }

        foreach($previousRequestsDatabaseRows_2 ?? [] as $r){
            $length = count($r);

            $longEnough = count($r) >= 3;

            if(!$longEnough){
                continue;
            }

            $keyOfLastSinceZeroBased = $length - 1;
            $keyOfSecondToLast = $keyOfLastSinceZeroBased - 1;
            $keyToGetFor = $keyOfSecondToLast;

            $select = $r[$keyToGetFor];

            $usersPreviousByRequestCookieId[$select->cookie_id] = $select;
        }

        /*
         * Get the second-to-last
         */

        $this->requestTracker->fireRequestTrackedEvents($requestEntities, $usersPreviousByRequestCookieId ?? []);

        $this->requestsThisChunk = $requestEntities ?? collect([]);
    }

    /**
     * @param $keysThisChunk
     */
    private function determineValuesThisChunk($keysThisChunk)
    {
        $this->valuesThisChunk = new Collection();

        foreach ($keysThisChunk as $keyThisChunk) {
            $values = $this->batchService->cache()->smembers($keyThisChunk);
            foreach($values as $value){
                $this->valuesThisChunk->push(unserialize($value));
            }
        }
    }

    /**
     * @return void
     */
    private function processResponses()
    {
        $responses = $this->getResponses($this->valuesThisChunk);

        if(empty($responses)){
            $this->responsesThisChunk = collect([]);
            return;
        }

        $statusCodes = $this->getOrCreateResponseAssociatedEntities($responses);

        $this->responsesThisChunk = $this->hydrateAndPersistResponses($responses, $statusCodes);
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
     * @param $statusCodes
     * @return Collection
     */
    private function hydrateAndPersistResponses($responses, $statusCodes)
    {
        foreach($responses as $responseData) {

            $responseEntity = new Response();

            // ---------------------------------------------------------------------------------------------------------
            // ---------- 1. simple|scalar values ----------------------------------------------------------------------
            // ---------------------------------------------------------------------------------------------------------

            $responseEntity->setRespondedOn($responseData['respondedOn']);
            $responseEntity->setResponseDurationMs($responseData['responseDurationMs']);

            // ---------------------------------------------------------------------------------------------------------
            // ---------- 2. association one: request ------------------------------------------------------------------
            // ---------------------------------------------------------------------------------------------------------

            $uuid = $responseData['uuid'];
            $requestToUse = $this->requestsThisChunk[$uuid] ?? null;

            $responseEntity->setRequest($requestToUse);

            // ---------------------------------------------------------------------------------------------------------
            // ---------- 3. association two: status_code --------------------------------------------------------------
            // ---------------------------------------------------------------------------------------------------------

            $statusCodeToUse = null;

            $statusCodeHashToFind = $responseData['status_code']['hash'] ?? null;

            foreach ($statusCodes as $candidateHash => $candidate) {
                if ($statusCodeHashToFind === $candidateHash) {
                    $statusCodeToUse = $candidate;
                }
            }

            $responseEntity->setStatusCode($statusCodeToUse);

            // ---------------------------------------------------------------------------------------------------------
            // ---------- 4. persist -----------------------------------------------------------------------------------
            // ---------------------------------------------------------------------------------------------------------

            try {
                $this->entityManager->persist($responseEntity);
            } catch (Exception $exception) {
                error_log($exception);
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
     * @return void
     */
    private function processRequestExceptions()
    {
        $requestExceptionsData = $this->valuesThisChunk->filter(
            function($candidate){
                return $candidate['type'] === 'request-exception';
            }
        );

        if($requestExceptionsData->isEmpty()){
            $this->requestExceptionsThisChunk = collect([]);
            return;
        }

        // -------------------------------------------------------------------------------------------------------------
        // ---------- 1. Get exceptions, create new as needed ----------------------------------------------------------
        // -------------------------------------------------------------------------------------------------------------

        $exceptions = $this->getForTypeAndKeyByHash($requestExceptionsData, 'exception');

        try{
            $entities[ExceptionEntity::class] = $this->getExistingBulkInsertNew(ExceptionEntity::class, $exceptions);
            $this->entityManager->flush();
        } catch (Exception $e) {
            error_log($e);
        }

        // -------------------------------------------------------------------------------------------------------------
        // ---------- 2. Map exceptions To RequestExceptions -----------------------------------------------------------
        // -------------------------------------------------------------------------------------------------------------

        $requestExceptionsData = $requestExceptionsData->map(
            function($singleRequestException) use ($entities)
            {
                $targetHash = $singleRequestException['exception']['hash'];

                $exceptionsToMapToRequestExceptions = $entities[ExceptionEntity::class];

                $exceptionToAttach = $exceptionsToMapToRequestExceptions[$targetHash] ?? null;

                if($exceptionToAttach) {
                    $singleRequestException['exception'] = $exceptionToAttach;
                }

                return $singleRequestException;
            }
        );


        // -------------------------------------------------------------------------------------------------------------
        // ---------- 3. Map requests to RequestExceptions -------------------------------------------------------------
        // -------------------------------------------------------------------------------------------------------------

        $matchedWithRequests = $this->matchWithRequest($requestExceptionsData, $this->requestsThisChunk);


        // -------------------------------------------------------------------------------------------------------------
        // ---------- 4. Create entities -------------------------------------------------------------------------------
        // -------------------------------------------------------------------------------------------------------------

        foreach($matchedWithRequests as $set){

            $requestExceptionData = $set['request-exception'];

            $exception = $requestExceptionData['exception'];

            $requestException = new RequestException();
            $requestException->setRequest($set['request']);
            $requestException->setException($exception);
            $requestException->setCreatedAtTimestampMs($requestExceptionData['createdAtTimestampMs']);

            $requestExceptions[] = $requestException;

            try{
                $this->entityManager->persist($requestException);
            }catch(Exception $exception){
                error_log($exception);
            }
        }

        // -------------------------------------------------------------------------------------------------------------
        // ---------- 5. Store entities --------------------------------------------------------------------------------
        // -------------------------------------------------------------------------------------------------------------

        try{
            $this->entityManager->flush();
        }catch(Exception $exception){
            error_log($exception);
        }

        $this->requestExceptionsThisChunk = collect($requestExceptions ?? []);
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
