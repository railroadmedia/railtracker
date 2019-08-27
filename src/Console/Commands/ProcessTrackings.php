<?php

namespace Railroad\Railtracker\Console\Commands;

use Exception;
use Illuminate\Cookie\CookieJar;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Railroad\Railtracker\Events\RequestTracked;
use Railroad\Railtracker\Repositories\RequestRepository;
use Railroad\Railtracker\Services\BatchService;
use Railroad\Railtracker\Services\IpDataApiSdkService;
use Railroad\Railtracker\Trackers\ExceptionTracker;
use Railroad\Railtracker\ValueObjects\ExceptionVO;
use Railroad\Railtracker\ValueObjects\RequestVO;

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
     * @var string
     */
    public static $cookieKey = 'railtracker_visitor';

    /**
     * @var BatchService
     */
    private $batchService;

    /**
     * @var ExceptionTracker
     */
    private $exceptionTracker;

    /**
     * @var IpDataApiSdkService
     */
    private $ipDataApiSdkService;

    /**
     * @var RequestRepository
     */
    private $requestRepository;

    /**
     * @var DatabaseManager
     */
    private $databaseManager;

    /**
     * @var CookieJar
     */
    private $cookieJar;

    /**
     * ProcessTrackings constructor.
     * @param BatchService $batchService
     * @param ExceptionTracker $exceptionTracker
     * @param IpDataApiSdkService $ipDataApiSdkService
     * @param RequestRepository $requestRepository
     * @param DatabaseManager $databaseManager
     * @param CookieJar $cookieJar
     */
    public function __construct(
        BatchService $batchService,
        ExceptionTracker $exceptionTracker,
        IpDataApiSdkService $ipDataApiSdkService,
        RequestRepository $requestRepository,
        DatabaseManager $databaseManager,
        CookieJar $cookieJar
    )
    {
        parent::__construct();

        $this->batchService = $batchService;
        $this->exceptionTracker = $exceptionTracker;
        $this->ipDataApiSdkService = $ipDataApiSdkService;
        $this->requestRepository = $requestRepository;
        $this->databaseManager = $databaseManager;
        $this->cookieJar = $cookieJar;
    }

    /**
     * return true
     */
    public function handle()
    {
        $redisIterator = null;

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

                $resultsCounts = $this->processRequests($valuesThisChunk);

            } catch (Exception $exception) {
                error_log($exception);
            }
        }

        $this->printMessage($resultsCounts ?? []);

        return true;
    }

    /**
     * @param Collection $objectsFromCache
     * @return array
     */
    private function processRequests(Collection $objectsFromCache)
    {
        $exceptionsCount = 0;

        $requestVOs = $objectsFromCache->filter(
            function ($candidate) {
                return $candidate instanceof RequestVO;
            }
        );

        if ($requestVOs->isEmpty()) {
            return [
                'requestsCount' => 0,
                'exceptionsCount' => $exceptionsCount
            ];
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
                $exceptionsCount++;
            }
        }
        $recordsInDatabase = $this->requestRepository->storeRequests($requestVOs);

        $this->updateUsersAnonymousRequests($recordsInDatabase);

        $usersPreviousRequestsByCookieId = $this->findUsersPreviousByRequestCookieId($requestVOs);

        $this->fireRequestTrackedEvents(
            $recordsInDatabase,
            $usersPreviousRequestsByCookieId
        );

        return [
            'requestsCount' => count($recordsInDatabase),
            'exceptionsCount' => $exceptionsCount
        ];
    }

    // -----------------------------------------------------------------------------------------------------------------
    // NEW -------------------------------------------------------------------------------------------------------------
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * @param Collection|RequestVO[] $requests
     * @return void
     */
    public function updateUsersAnonymousRequests(Collection $requests)
    {
        foreach($requests as $request){
            $userId = $request->user_id;
            $cookieId = $request->cookie_id;

            $table = config('railtracker.table_prefix') . 'requests'; // todo: a more proper way to get this?

            if (!empty($userId) && !empty($cookieId)) {
                $this->databaseManager->table($table)
                    ->where(['cookie_id' => $cookieId])
                    ->whereNull('user_id')
                    ->update(['user_id' => $userId]);

                // delete cookie
                $this->cookieJar->queue($this->cookieJar->forget(self::$cookieKey));
            }
        }
    }

    /**
     * @param Collection|array[] $requestRecords
     * @param array $usersPreviousRequestsByCookieId
     */
    public function fireRequestTrackedEvents($requestRecords, $usersPreviousRequestsByCookieId = [])
    {
        foreach($requestRecords as $requestRecord){

            $userHasPreviousRequest = !empty($usersPreviousRequestsByCookieId[$requestRecord->cookie_id]);

            if($userHasPreviousRequest){
                $previousRequest = $usersPreviousRequestsByCookieId[$requestRecord->cookie_id];
                $timeOfPreviousRequest = $previousRequest->requested_on;
            }

            event(
                new RequestTracked(
                    $requestRecord->id,
                    $requestRecord->user_id,
                    $requestRecord->ip_address,
                    $requestRecord->agent_string,
                    $requestRecord->requested_on,
                    $timeOfPreviousRequest ?? null
                )
            );
        }
    }

    /**
     * @param RequestVO $requestVO
     * @return array
     */
    public function getPreviousRequestsDatabaseRows($requestVO)
    {
        $table = config('railtracker.table_prefix') . 'requests';

        $results = $this->databaseManager->table($table)
            ->where(['user_id' => $requestVO->userId])
            ->get()
            ->all();

        return $results;
    }

    // -----------------------------------------------------------------------------------------------------------------
    // used only by handle ---------------------------------------------------------------------------------------------
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * @param $resultsCounts
     */
    public function printMessage($resultsCounts)
    {
        if (getenv('APP_ENV') !== 'testing') {
            $requestsCount = $resultsCounts['requestsCount'] ?? 0;
            $exceptionsCount = $resultsCounts['exceptionsCount'] ?? 0;
            $successfulRequestsCount = $requestsCount - $exceptionsCount;
            $this->info(
                'Number of requests processed (without and with exceptions respectively): ' . $successfulRequestsCount .
                ', ' . $exceptionsCount
            );
        }
    }

    // -----------------------------------------------------------------------------------------------------------------
    // helper methods for processing requests --------------------------------------------------------------------------
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * @param RequestVO[]|Collection $requestVOs
     * @return array
     */
    private function findUsersPreviousByRequestCookieId($requestVOs)
    {
        foreach ($requestVOs as $requestVO) {
            if ($requestVO->userId !== null) {
                $previousRequests = $this->getPreviousRequestsDatabaseRows($requestVO);
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

                $dbRowWithIpData = $this->requestRecordMatchingIp($requestVO, $matchingRequests)->first();

                $requestVO->ipLatitude = $dbRowWithIpData->ip_latitude ?? null;
                $requestVO->ipLongitude = $dbRowWithIpData->ip_longitude ?? null;
                $requestVO->ipCountryCode = $dbRowWithIpData->ip_country_code ?? null;
                $requestVO->ipCountryName = $dbRowWithIpData->ip_country_name ?? null;
                $requestVO->ipRegion = $dbRowWithIpData->ip_region_name ?? null;
                $requestVO->ipCity = $dbRowWithIpData->ip_city ?? null;
                $requestVO->ipPostalZipCode = $dbRowWithIpData->ip_postal_zip_code ?? null;
                $requestVO->ipTimezone = $dbRowWithIpData->ip_timezone ?? null;
                $requestVO->ipCurrency = $dbRowWithIpData->ip_currency ?? null;
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

            if(!empty($ipDataForRequestVO)){
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

}
