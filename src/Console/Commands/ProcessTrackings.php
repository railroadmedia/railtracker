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
use Railroad\Railtracker\Repositories\RequestRepository;
use Railroad\Railtracker\Services\BatchService;
use Railroad\Railtracker\Services\IpDataApiSdkService;
use Railroad\Railtracker\Trackers\ExceptionTracker;
use Railroad\Railtracker\Trackers\RequestTracker;
use Railroad\Railtracker\Trackers\ResponseTracker;
use Railroad\Railtracker\ValueObjects\ExceptionVO;
use Railroad\Railtracker\ValueObjects\RequestVO;
use Throwable;

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
     * @var IpDataApiSdkService
     */
    private $ipDataApiSdkService;

    /**
     * @var RequestRepository
     */
    private $requestRepository;

    /**
     * ProcessTrackings constructor.
     * @param BatchService $batchService
     * @param RequestTracker $requestTracker
     * @param ExceptionTracker $exceptionTracker
     * @param ResponseTracker $responseTracker
     * @param RailtrackerEntityManager $entityManager
     * @param IpDataApiSdkService $ipDataApiSdkService
     * @param RequestRepository $requestRepository
     */
    public function __construct(
        BatchService $batchService,
        RequestTracker $requestTracker,
        ExceptionTracker $exceptionTracker,
        ResponseTracker $responseTracker,
        RailtrackerEntityManager $entityManager,
        IpDataApiSdkService $ipDataApiSdkService,
        RequestRepository $requestRepository
    )
    {
        parent::__construct();

        $this->batchService = $batchService;
        $this->requestTracker = $requestTracker;
        $this->exceptionTracker = $exceptionTracker;
        $this->responseTracker = $responseTracker;
        $this->entityManager = $entityManager;
        $this->ipDataApiSdkService = $ipDataApiSdkService;
        $this->requestRepository = $requestRepository;
    }

    /**
     * return true
     */
    public function handle()
    {
        $redisIterator = null;
        $counts = ['requests' => 0, 'reqExc' => 0, 'responses' => 0];

        while ($redisIterator !== 0) {

            try {
                $scanResult = $this->batchService->cache()->scan(
                        $redisIterator,
                        [
                            'MATCH' => $this->batchService->batchKeyPrefix . '*',
                            'COUNT' => config('railtracker.scan-size', 1000)
                        ]
                    );
                $redisIterator = (integer)$scanResult[0];
                $keys = $scanResult[1];

                if (empty($keys)) {
                    continue;
                }

                $valuesThisChunk = new Collection();

                foreach ($keys as $keyThisChunk) {
                    $values = $this->batchService->cache()->smembers($keyThisChunk);

                    foreach ($values as $value) {
                        $valuesThisChunk->push(unserialize($value));
                    }
                }

                $this->batchService->forget($keys);

                // this here?
//                // todo: change?
//                $this->getGeoIpEntitiesCreateWhereNeeded()


                $this->processRequests($valuesThisChunk);

                continue;

                // todo: exceptions!
                //$this->processRequestExceptions();

                $this->entityManager->clear();

                $counts = $this->incrementCountersForOutputMessage($counts);

            } catch (Exception $exception) {
                error_log($exception);
            }
        }

       $this->printTotalResultsInfo($counts);

        return true;
    }

    /**
     * @param Collection $objectsFromCache
     * @return void
     */
    private function processRequests(Collection $objectsFromCache)
    {
        $requestVOs = $objectsFromCache->filter(
            function ($candidate) {
                return $candidate instanceof RequestVO;
            }
        );

        if ($requestVOs->isEmpty()) {
            return;
        }

        $this->requestRepository->removeDuplicateVOs($requestVOs);

        $requestVOs = $this->getAndAttachGeoIpData($requestVOs);

        foreach($objectsFromCache as $item){
            $type = get_class($item);
            $isExceptionVO = $type === ExceptionVO::class;
            if($isExceptionVO){ /** @var ExceptionVO $item */
                $uuid = $item->uuid;
                $exceptionVOs[$uuid] = $item;
            }
        }

        foreach($requestVOs as $requestVO){ /** @var RequestVO $requestVO */
            $uuid = $requestVO->uuid;
            if(!empty($exceptionVOs[$uuid])){
                $matchingExceptionVO = $exceptionVOs[$uuid];
                $requestVO->exceptionCode = $matchingExceptionVO->code;
                $requestVO->exceptionLine = $matchingExceptionVO->line;
                $requestVO->exceptionClass = $matchingExceptionVO->class;
                $requestVO->exceptionFile = $matchingExceptionVO->file;
                $requestVO->exceptionMessage = $matchingExceptionVO->message;
                $requestVO->exceptionTrace = $matchingExceptionVO->trace;
            }
        }
        $recordsInDatabase = $this->requestRepository->storeRequests($requestVOs);

        $this->requestTracker->updateUsersAnonymousRequests($recordsInDatabase);

        $usersPreviousRequestsByCookieId = $this->findUsersPreviousByRequestCookieId($requestVOs);

        $this->requestTracker->fireRequestTrackedEvents(
            $recordsInDatabase,
            $usersPreviousRequestsByCookieId
        );

        return;
    }

    /**
     * @return void
     */
    private function processRequestExceptions()
    {
        $this->requestExceptionsThisChunk = collect([]);

        $requestExceptionsData = $this->valuesThisChunk->filter(
            function ($candidate) {
                return $candidate['type'] === 'request-exception';
            }
        );

        if ($requestExceptionsData->isEmpty()) {
            return;
        }

        $requestExceptionsData = $this->getExceptionsAndMapToExceptionRequests($requestExceptionsData);

        $exceptionsMatchedWithRequests = $this->matchWithRequest($requestExceptionsData, $this->requestsThisChunk);

        $this->createRequestExceptions($exceptionsMatchedWithRequests);
    }

    /**
     * @return void
     */
    private function processResponses()
    {
        $responses = $this->getResponses($this->valuesThisChunk);

        if (empty($responses)) {
            $this->responsesThisChunk = collect([]);

            return;
        }

        $statusCodes = $this->getOrCreateResponseAssociatedEntities($responses);

        $this->responsesThisChunk = $this->hydrateAndPersistResponses($responses, $statusCodes);
    }


    // -----------------------------------------------------------------------------------------------------------------
    // used only by handle ---------------------------------------------------------------------------------------------
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * @param array $counts
     * @return array
     */
    private function incrementCountersForOutputMessage($counts)
    {
        $counts['requests'] = ($counts['requests'] ?? 0) + $this->requestsThisChunk->count();
        $counts['reqExc'] = ($counts['reqExc'] ?? 0) + $this->requestExceptionsThisChunk->count();
        $counts['responses'] = ($counts['responses'] ?? 0) + $this->responsesThisChunk->count();

        return $counts;
    }

    /**
     * @param array $counts
     * @return void
     */
    private function printTotalResultsInfo($counts)
    {
        $output =
            'Processed ' .
            $counts['requests'] .
            ' ' .
            ($counts['requests'] === 1 ? 'request' : 'requests') .
            ', ' .
            $counts['reqExc'] .
            ' ' .
            ($counts['reqExc'] === 1 ? 'requestException' : 'requestExceptions') .
            ', and ' .
            $counts['responses'] .
            ' ' .
            ($counts['responses'] === 1 ? 'response' : 'responses') .
            '.';

        if (getenv('APP_ENV') !== 'testing') {
            $this->info($output);
        }
    }

    // -----------------------------------------------------------------------------------------------------------------
    // helper methods for processing responses -------------------------------------------------------------------------
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * @param $valuesThisChunk
     * @return mixed
     */
    private function getResponses($valuesThisChunk)
    {
        return $valuesThisChunk->filter(
            function ($candidate) {
                return $candidate['type'] === 'response';
            }
        );
    }

    /**
     * @param $responses
     * @param $statusCodes
     * @return Collection
     */
    private function hydrateAndPersistResponses($responses, $statusCodes)
    {
        foreach ($responses as $responseData) {

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

        try {
            $this->entityManager->flush();
        } catch (Exception $e) {
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


    // -----------------------------------------------------------------------------------------------------------------
    // helper methods for processing requests --------------------------------------------------------------------------
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * @param Collection $requests
     * @return Collection
     */
    private function getAndMapUrlsFromRequests(Collection $requests)
    {
        $urlsNotKeyed = collect(
            array_merge(
                $requests->map(
                    function ($request) {
                        return $request[Url::$KEY];
                    }
                )->all(),
                $requests->map(
                    function ($request) {
                        return $request[Url::$REFERER_URL_KEY];
                    }
                )->all()
            )
        );

        return collect($this->keyByHash($urlsNotKeyed));
    }

    /**
     * @param Collection $requests
     * @param $entities
     * @param $geoIpEntitiesKeyedByIp
     * @return array
     */
    private function createRequestEntitiesAndAttachAssociatedEntities(
        Collection $requests,
        $entities,
        $geoIpEntitiesKeyedByIp
    )
    {
        $requestEntitiesByUuid = [];

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

        foreach ($requests as &$requestData) {
            foreach ($associationsClassesAndKeys as $key => $class) {
                $hashRequired = $requestData[$key]['hash'];
                $candidates = $entities[$class];

                if (isset($candidates[$hashRequired])) {
                    $requestData[$key] = $candidates[$hashRequired];
                }
            }

            try {
                $r = new Request();
                $r->setUuid($requestData['uuid']);
                $r->setUserId($requestData['userId']);
                $r->setCookieId($requestData['cookieId']);
                $r->setIsRobot($requestData['isRobot']);
                $r->setRequestedOn(Carbon::parse($requestData['requestedOn']));
                $r->setAgent($requestData['agent']);
                $r->setDevice($requestData['device']);
                $r->setLanguage($requestData['language']);
                $r->setMethod($requestData['method']);
                $r->setClientIp($requestData['clientIp']);
                $r->setGeoip($geoIpEntitiesKeyedByIp[$requestData['clientIp']] ?? null);

                /*
                 * url_id, referer_url_id, and route_id columns of table are nullable, thus only set if entity available
                 * here. We have to check for object of type because if object is not set an array will be, but trying
                 * to set this will cause an error.
                 */
                if (is_a($requestData['url'], Url::class)) {
                    $r->setUrl($requestData['url']);
                }
                if (is_a($requestData['refererUrl'], Url::class)) {
                    $r->setRefererUrl($requestData['refererUrl']);
                }
                if (is_a($requestData['route'], Route::class)) {
                    $r->setRoute($requestData['route']);
                }

                $this->entityManager->persist($r);

                $requestEntitiesByUuid[$r->getUuid()] =
                    $r ?? null; // todo: why this ?? operator? Delete if shouldn't be here

            } catch (Exception $e) {
                error_log($e);
            }
        }

        try {
            $this->entityManager->flush();
        } catch (Exception $e) {
            error_log($e);
        }

        $this->requestsThisChunk = collect($requestEntitiesByUuid);
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
            $entities[RequestAgent::class] = $this->getExistingBulkInsertNew(RequestAgent::class, $mappedAgents);

            $entities[RequestDevice::class] = $this->getExistingBulkInsertNew(RequestDevice::class, $mappedDevices);

            $entities[RequestLanguage::class] =
                $this->getExistingBulkInsertNew(RequestLanguage::class, $mappedLanguages);

            $entities[RequestMethod::class] = $this->getExistingBulkInsertNew(RequestMethod::class, $mappedMethods);

            $entities[Route::class] = $this->getExistingBulkInsertNew(Route::class, $mappedRoutes);

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

        try {
            $entities[UrlProtocol::class] = $this->getExistingBulkInsertNew(UrlProtocol::class, $mappedUrlProtocols);

            $entities[UrlDomain::class] = $this->getExistingBulkInsertNew(UrlDomain::class, $mappedUrlDomains);

            $entities[UrlPath::class] = $this->getExistingBulkInsertNew(UrlPath::class, $mappedUrlPaths);

            $entities[UrlQuery::class] = $this->getExistingBulkInsertNew(UrlQuery::class, $mappedUrlQueries);

            $this->entityManager->flush();

        } catch (Exception $e) {
            error_log($e);
        }

        return $entities;
    }

    /**
     * @param Collection $mappedUrls
     * @param $entities
     * @return array
     */
    private function mapChildrenToUrls(Collection $mappedUrls, $entities)
    {
        return $mappedUrls->map(
            function ($url) use ($entities) {
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
                foreach ($keys as $key) {

                    if (empty($url[$key])) {
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
            }
        )->all();
    }

    /**
     * @param $requests
     * @return array
     */
    private function findUsersPreviousByRequestCookieId($requests)
    {
        foreach ($requests as $request) {
            if ($request->userId !== null) {
                $previousRequests = $this->requestTracker->getPreviousRequestsDatabaseRows($request);
                $enough = count($previousRequests) >= 2;
                if (!$enough) {
                    continue;
                }
                end($previousRequests);
                $secondMostRecent = prev($previousRequests);
                $usersPreviousByRequestCookieId[$secondMostRecent->cookie_id] = $secondMostRecent;
            }
        }

        return $usersPreviousByRequestCookieId ?? [];
    }

    /**
     * @param Collection|RequestVO $requests
     * @return Collection
     */
    private function getGeoIpData(Collection $requests)
    {
        $ips = $requests->map(
            function ($request) {
                /** @var RequestVO $request */
                return $request->ipAddress;
            }
        )->toArray();

        $results = $this->ipDataApiSdkService->bulkRequest($ips);

        return collect($results);
    }

    /**
     * @param Collection|RequestVO[] $requestVOs
     * @return Collection
     */
    private function getAndAttachGeoIpData(Collection $requestVOs)
    {
        /*
         * Don't query the API for data we already have in DB. Instead, get most recent request row with ip_address
         * matching ips in our RequestVOs, then split those RequestVOs out and fill in the ip fields. *Then*
         * with the remaining RequestVOs query the API, using the results to fill in fields.
         */

        $matchingRequests = $this->requestRepository->getMostRecentRequestForEachIpAddress($requestVOs ?? []);

        // split VOs into those with ipData available from previous requests and those for which we have to query the API

        list($requestVOsNotRequiringApiCall, $requestVOsRequiringApiQuery) = $requestVOs->partition(
            // if return true, value will be passed to param 1, if false then passed to param 2
            function($requestVO) use ($matchingRequests){

                $matchingRequestsForIpAddress = $this->requestRecordMatchingIp($requestVO, $matchingRequests);

                $matchExists = $matchingRequestsForIpAddress->count() > 0;

                return $matchExists;
            }
        );

        $requestVOsNotRequiringApiCall = collect($requestVOsNotRequiringApiCall ?? []);
        $requestVOsRequiringApiQuery = collect($requestVOsRequiringApiQuery ?? []);

        // for those RequestVOs that can get their geo-ip data from existing records, do that.

        $requestVOsNotRequiringApiCall->map(
            function($requestVO) use($matchingRequests){

                $matchingRequestsForIpAddress = $this->requestRecordMatchingIp($requestVO, $matchingRequests);

                if($matchingRequestsForIpAddress->isEmpty()){
                    error_log('There shouldn\' be any empty results here');
                }

                $matchingRequestForIpAddress = $matchingRequestsForIpAddress->first();

                $requestVO->ipLatitude = $matchingRequestForIpAddress->ip_latitude;
                $requestVO->ipLongitude = $matchingRequestForIpAddress->ip_longitude;
                $requestVO->ipCountryCode = $matchingRequestForIpAddress->ip_country_code;
                $requestVO->ipCountryName = $matchingRequestForIpAddress->ip_country_name;
                $requestVO->ipRegion = $matchingRequestForIpAddress->ip_region;
                $requestVO->ipCity = $matchingRequestForIpAddress->ip_city;
                $requestVO->ipPostalZipCode = $matchingRequestForIpAddress->ip_postal_zip_code;
                $requestVO->ipTimezone = $matchingRequestForIpAddress->ip_timezone;
                $requestVO->ipCurrency = $matchingRequestForIpAddress->ip_currency;
            }
        );

        // for those RequestVOs that get geo-ip data from API, do that.

        $geoIpData = $this->getGeoIpData($requestVOsRequiringApiQuery);

        $requestVOsRequiringApiQuery->map(function($requestVO) use ($geoIpData){
            /** @var RequestVO $requestVO */
            $ipAddress = $requestVO->ipAddress;

            $ipDataForRequestVO = $geoIpData->filter(
                function($candidate) use ($ipAddress){
                    return $ipAddress === ($candidate['ip'] ?? null);
                }
            )->first();

            $requestVO->ipLatitude = $ipDataForRequestVO['latitude'];
            $requestVO->ipLongitude = $ipDataForRequestVO['longitude'];
            $requestVO->ipCountryCode = $ipDataForRequestVO['country_code'];
            $requestVO->ipCountryName = $ipDataForRequestVO['country_name'];
            $requestVO->ipRegion = $ipDataForRequestVO['region_code'];
            $requestVO->ipCity = $ipDataForRequestVO['city'];
            $requestVO->ipPostalZipCode = $ipDataForRequestVO['postal'];
            if(!empty($ipDataForRequestVO['time_zone'])){
                $requestVO->ipTimezone = $ipDataForRequestVO['time_zone']->name;
            }
            if(!empty($ipDataForRequestVO['currency'])){
                $requestVO->ipCurrency = $ipDataForRequestVO['currency']->code;
            }
        });

        // merge and return

        $requestVOsWithGeoIpData = $requestVOsRequiringApiQuery->merge($requestVOsNotRequiringApiCall);

        return $requestVOsWithGeoIpData;
    }

    // -----------------------------------------------------------------------------------------------------------------
    // helper methods for processing request exceptions ----------------------------------------------------------------
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * @param RequestVO $requestVO
     * @param Collection $matchingRequests
     * @return Collection
     */
    private function requestRecordMatchingIp(RequestVO $requestVO, Collection $matchingRequests)
    {
        $ipAddress = $requestVO->ipAddress;
        $matchingRequestsForIpAddress = $matchingRequests->where('ip_address', $ipAddress);

        if($matchingRequestsForIpAddress->count() > 1){
            error_log('There should only be one.');
        }

        return $matchingRequestsForIpAddress;
    }

    /**
     * @param Collection $requestExceptionsData
     * @param $entities
     * @return Collection
     */
    private function getExceptionsAndMapToExceptionRequests(Collection $requestExceptionsData)
    {
        $entities = [];

        $exceptions = $this->getForTypeAndKeyByHash($requestExceptionsData, 'exception');

        try {
            $entities[ExceptionEntity::class] = $this->getExistingBulkInsertNew(ExceptionEntity::class, $exceptions);
            $this->entityManager->flush();
        } catch (Exception $e) {
            error_log($e);
        }

        return $requestExceptionsData->map(
            function ($singleRequestException) use ($entities) {
                $targetHash = $singleRequestException['exception']['hash'];

                $exceptionsToMapToRequestExceptions = $entities[ExceptionEntity::class];

                $exceptionToAttach = $exceptionsToMapToRequestExceptions[$targetHash] ?? null;

                if ($exceptionToAttach) {
                    $singleRequestException['exception'] = $exceptionToAttach;
                }

                return $singleRequestException;
            }
        );
    }

    /**
     * @param array $exceptionsMatchedWithRequests
     */
    private function createRequestExceptions($exceptionsMatchedWithRequests)
    {
        foreach ($exceptionsMatchedWithRequests as $set) {

            $requestExceptionData = $set['request-exception'];

            $exception = $requestExceptionData['exception'];

            $requestException = new RequestException();
            $requestException->setRequest($set['request']);
            $requestException->setException($exception);
            $requestException->setCreatedAtTimestampMs($requestExceptionData['createdAtTimestampMs']);

            $requestExceptions[] = $requestException;

            try {
                $this->entityManager->persist($requestException);
            } catch (Exception $exception) {
                error_log($exception);
            }
        }

        try {
            $this->entityManager->flush();
        } catch (Exception $exception) {
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
        foreach ($requestExceptions as $requestException) {

            $requestExceptionUuid = $requestException['uuid'];

            $matchingRequest = $requests->filter(
                function ($request) use ($requestExceptionUuid) {
                    /** @var Request $request */
                    $requestUuid = $request->getUuid();

                    return $requestUuid === $requestExceptionUuid;
                }
            )->first();

            $matched[$requestExceptionUuid] = [
                'request' => $matchingRequest,
                'request-exception' => $requestException,
            ];
        }

        return $matched ?? [];
    }


    // -----------------------------------------------------------------------------------------------------------------
    // helper functions, level one (used by entity-processing methods) -------------------------------------------------
    // -----------------------------------------------------------------------------------------------------------------

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
     * @param Collection $urls
     * @param $keyForRequired
     * @return Collection
     */
    private function filterForSetEntitiesOfAType(Collection $urls, $keyForRequired)
    {
        $existant = $urls->filter(
            function ($url) use ($keyForRequired) {
                return !empty($url[$keyForRequired]);
            }
        )->all();

        return collect($existant);
    }

    /**
     * @param Collection $items
     * @param $keyToMap
     * @return array
     */
    private function getForTypeAndKeyByHash(Collection $items, $keyToMap)
    {
        $mappedEntities = $items->map(
            function ($item) use ($keyToMap) {
                return $item[$keyToMap];
            }
        )->all();

        return $this->keyByHash($mappedEntities);
    }

    /**
     * @param $data
     * @return array
     */
    private function keyByHash($data)
    {
        foreach ($data as $datum) {
            if (isset($datum['hash'])) {
                $arraysByHash[$datum['hash']] = $datum;
            }
        }

        return $arraysByHash ?? [];
    }


    // -----------------------------------------------------------------------------------------------------------------
    // helper-helper functions, level two (used by helper functions) ---------------------------------------------------
    // -----------------------------------------------------------------------------------------------------------------

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
            $qb->select('a')->from($class, 'a')->where('a.hash IN (:whereValues)')->setParameter(
                    'whereValues',
                    array_keys($arraysByHash)
                )->getQuery()->getResult();

        $existingEntitiesByHash = [];

        // key by hash
        foreach ($existingEntities as $existingEntity) {
            $existingEntitiesByHash[$existingEntity->getHash()] = $existingEntity;
        }

        return $existingEntitiesByHash;
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
            }
            else {
                try {
                    $entity = $this->processForType($class, $entity);

                    if ($entity->allValuesAreEmpty()) {
                        continue;
                    }

                    $entities[$entity->getHash()] = $entity;

                    $this->entityManager->persist($entity);
                } catch (Exception $exception) {
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
}
