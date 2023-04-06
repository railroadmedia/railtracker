<?php

namespace Railroad\Railtracker\Console\Commands;

use Exception;
use Illuminate\Cookie\CookieJar;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
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
    ) {
        parent::__construct();

        $this->batchService = $batchService;
        $this->exceptionTracker = $exceptionTracker;
        $this->ipDataApiSdkService = $ipDataApiSdkService;
        $this->requestRepository = $requestRepository;
        $this->databaseManager = $databaseManager;
        $this->cookieJar = $cookieJar;
    }

    public function handle()
    {
        $instance = rand(1000, 9999);
        $this->info("$instance:$this->name Processing");
        $timeStart = microtime(true);

        $redisIterator = null;

        $exceptionsTrackedCount = 0;
        $successfulRequestsCount = 0;

        $timedout = false;

        while ($redisIterator !== 0) {
            try {
                $diff = microtime(true) - $timeStart;
                if ($diff > 55) {
                    $timedout = true;
                    break;
                }

                $scanResult = $this->batchService->connection()->scan(
                    $redisIterator,
                    [
                        'match' => $this->batchService->batchKeyPrefix . '*',
                        'count' => config('railtracker.scan-size', 1000)
                    ]
                );

                $redisIterator = $scanResult ? (integer)$scanResult[0] : 0;
                $keys = $scanResult ? $scanResult[1] : null;

                if (empty($keys)) {
                    continue;
                }

                $valuesThisChunk = new Collection();

                foreach ($keys as $keyThisChunk) {
                    $values = $this->batchService->connection()->smembers($keyThisChunk);

                    foreach ($values as $value) {
                        $valuesThisChunk->push(unserialize($value));
                    }
                }

                //$this->info('Starting to process ' . count($keys) . ' items.');

                $this->batchService->forget($keys);

                try {
                    $resultsCount = $this->processRequests($valuesThisChunk);
                } catch (\Exception $e) {
                    Log::error($e);
                }

                if (!empty($resultsCount)) {
                    $totalRequestsCount = $resultsCount['requestsCount'];
                    $exceptionsTrackedCount = $exceptionsTrackedCount + $resultsCount['exceptionsTrackedCount'];
                    $successfulRequestsCount =
                        $successfulRequestsCount + $totalRequestsCount - $resultsCount['exceptionsTrackedCount'];
                }
            } catch (Exception $exception) {
                Log::error($e);
            }
        }

        $this->info("$instance:$this->name # requests success: $successfulRequestsCount");
        if ($exceptionsTrackedCount > 0) {
            $this->info(
                "$instance:$this->name # requests failed: $exceptionsTrackedCount"
            );
        }

        $diff = microtime(true) - $timeStart;
        $sec = number_format((float)$diff, 3, '.', '');
        if ($timedout) {
            $this->info("$instance:$this->name Timeout ($sec s)");
        } else {
            $this->info("$instance:$this->name Finished ($sec s)");
        }
    }

    public function info($string, $verbosity = null)
    {
        Log::info($string); //also write info statements to log
        $this->line($string, 'info', $verbosity);
    }


    /**
     * @param Collection $objectsFromCache
     * @return array
     */
    private function processRequests(Collection $objectsFromCache)
    {
        $exceptionsTrackedCount = 0;

        $requestVOs = $objectsFromCache->filter(
            function ($candidate) {
                return $candidate instanceof RequestVO;
            }
        );

        if ($requestVOs->isEmpty()) {
            return [
                'requestsCount' => 0,
                'exceptionsTrackedCount' => $exceptionsTrackedCount
            ];
        }

        $this->requestRepository->removeDuplicateVOs($requestVOs);

        $requestVOs = $this->getAndAttachGeoIpData($requestVOs);

        foreach ($objectsFromCache as $item) {
            $type = get_class($item);
            $isExceptionVO = $type === ExceptionVO::class;
            if ($isExceptionVO) {
                /** @var ExceptionVO $item */
                $uuid = $item->uuid;
                $exceptionVOs[$uuid] = $item;
            }
        }

        foreach ($requestVOs as $requestVO) {
            /** @var RequestVO $requestVO */
            $uuid = $requestVO->uuid;
            if (!empty($exceptionVOs[$uuid])) {
                $matchingExceptionVO = $exceptionVOs[$uuid];

                $requestVO->exceptionCode = $matchingExceptionVO->code;
                $requestVO->exceptionLine = $matchingExceptionVO->line;

                if (!empty($matchingExceptionVO->class)) {
                    $requestVO->exceptionClass = $matchingExceptionVO->class;
                    $requestVO->exceptionClassHash = md5($requestVO->exceptionClass);
                }

                if (!empty($matchingExceptionVO->file)) {
                    $requestVO->exceptionFile = $matchingExceptionVO->file;
                    $requestVO->exceptionFileHash = md5($requestVO->exceptionFile);
                }

                if (!empty($matchingExceptionVO->message)) {
                    $requestVO->exceptionMessage = $matchingExceptionVO->message;
                    $requestVO->exceptionMessageHash = md5($requestVO->exceptionMessage);
                }

                if (!empty($matchingExceptionVO->trace)) {
                    $requestVO->exceptionTrace = $matchingExceptionVO->trace;
                    $requestVO->exceptionTraceHash = md5($requestVO->exceptionTrace);
                }

                $exceptionsTrackedCount++;
            }
        }

        $recordsInDatabase = $this->requestRepository->storeRequests($requestVOs);

        $this->updateUsersAnonymousRequests($recordsInDatabase);

        return [
            'requestsCount' => count($recordsInDatabase),
            'exceptionsTrackedCount' => $exceptionsTrackedCount
        ];
    }

    /**
     * @param Collection|RequestVO[] $requests
     * @return void
     */
    public function updateUsersAnonymousRequests(Collection $requests)
    {
        foreach ($requests as $request) {
            $userId = $request->user_id;
            $cookieId = $request->cookie_id;

            $table = config('railtracker.table_prefix') . 'requests'; // todo: a more proper way to get this?

            if (!empty($userId) && !empty($cookieId)) {
                $chunkSize = config('railtracker.updateUsersAnonymousRequests_processing_chunk_size') ?? 1000;

                $this->databaseManager->table($table)
                    ->select('id')
                    ->where(['cookie_id' => $cookieId])
                    ->whereNull('user_id')
                    ->orderBy('id')
                    ->chunk($chunkSize, function ($ids) use ($table, $userId) {
                        /** @var $ids Collection */
                        $this->databaseManager->table($table)
                            ->whereIn('id', $ids->pluck('id')->toArray())
                            ->update(['user_id' => $userId]);
                    });

                // delete cookie
                $this->cookieJar->queue($this->cookieJar->forget(self::$cookieKey));
            }
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

        [$requestVOsNotRequiringApiCall, $requestVOsRequiringApiQuery] = $requestVOs->partition(
        // if return true, value will be passed to param 1, if false then passed to param 2
            function ($requestVO) use ($matchingRequests) {
                $matchingRequestsForIpAddress = $this->requestRecordMatchingIp($requestVO, $matchingRequests);

                $matchExists = $matchingRequestsForIpAddress->count() > 0;

                return $matchExists;
            }
        );

        $requestVOsNotRequiringApiCall = collect($requestVOsNotRequiringApiCall ?? []);
        $requestVOsRequiringApiQuery = collect($requestVOsRequiringApiQuery ?? []);

        // for those RequestVOs that can get their geo-ip data from existing records, do that.

        $requestVOsNotRequiringApiCall->map(
            function ($requestVO) use ($matchingRequests) {
                $dbRowWithIpData = $this->requestRecordMatchingIp($requestVO, $matchingRequests)->first();

                $requestVO->ipLatitude = $dbRowWithIpData->ip_latitude ?? null;
                $requestVO->ipLongitude = $dbRowWithIpData->ip_longitude ?? null;
                $requestVO->ipCountryCode = $dbRowWithIpData->ip_country_code ?? null;
                $requestVO->ipCountryName = $dbRowWithIpData->ip_country_name ?? null;
                $requestVO->ipRegion = $dbRowWithIpData->ip_region ?? null;
                $requestVO->ipCity = $dbRowWithIpData->ip_city ?? null;
                $requestVO->ipPostalZipCode = $dbRowWithIpData->ip_postal_zip_code ?? null;
                $requestVO->ipTimezone = $dbRowWithIpData->ip_timezone ?? null;
                $requestVO->ipCurrency = $dbRowWithIpData->ip_currency ?? null;
            }
        );

        // for those RequestVOs that get geo-ip data from API, do that.

        $geoIpData = $this->getGeoIpData($requestVOsRequiringApiQuery);

        $requestVOsRequiringApiQuery->map(function ($requestVO) use ($geoIpData) {
            /** @var RequestVO $requestVO */
            $ipAddress = $requestVO->ipAddress;

            $ipDataForRequestVO = $geoIpData->filter(
                function ($candidate) use ($ipAddress) {
                    return $ipAddress === ($candidate['ip'] ?? null);
                }
            )->first();

            if (!empty($ipDataForRequestVO)) {
                $requestVO->ipLatitude = $ipDataForRequestVO['latitude'];
                $requestVO->ipLongitude = $ipDataForRequestVO['longitude'];
                $requestVO->ipCountryCode = $ipDataForRequestVO['country_code'];
                $requestVO->ipCountryName = $ipDataForRequestVO['country_name'];
                $requestVO->ipRegion = $ipDataForRequestVO['region_code'];
                $requestVO->ipCity = $ipDataForRequestVO['city'];
                $requestVO->ipPostalZipCode = $ipDataForRequestVO['postal'];
                if (!empty($ipDataForRequestVO['time_zone'])) {
                    $requestVO->ipTimezone = $ipDataForRequestVO['time_zone']->name;
                }
                if (!empty($ipDataForRequestVO['currency'])) {
                    $requestVO->ipCurrency = $ipDataForRequestVO['currency']->code;
                }
            }
        });

        // merge
        $requestVOsWithGeoIpData = $requestVOsRequiringApiQuery->merge($requestVOsNotRequiringApiCall);

        // set IP fields to null if they are an empty string
        $ipFields = [
            'ipAddress',
            'ipLatitude',
            'ipLongitude',
            'ipCountryCode',
            'ipCountryName',
            'ipRegion',
            'ipCity',
            'ipPostalZipCode',
            'ipTimezone',
            'ipCurrency',
        ];

        foreach ($requestVOsWithGeoIpData as $requestVO) {
            foreach ($ipFields as $ipField) {
                if (empty($requestVO->$ipField)) {
                    $requestVO->$ipField = null;
                }
            }
        }

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

        return $matchingRequestsForIpAddress;
    }
}
