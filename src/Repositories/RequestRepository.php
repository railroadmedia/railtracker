<?php

namespace Railroad\Railtracker\Repositories;

use Illuminate\Support\Collection;
use Railroad\Railtracker\QueryBuilders\BulkInsertOrUpdateBuilder;
use Railroad\Railtracker\QueryBuilders\BulkInsertOrUpdateMySqlGrammar;
use Railroad\Railtracker\QueryBuilders\BulkInsertOrUpdateSqlLiteGrammar;
use Railroad\Railtracker\ValueObjects\RequestVO;

class RequestRepository extends TrackerRepositoryBase
{
    private static $requestAttributeTableColumnMap = [
        'uuid' => [
            'table' => 'requests',
            'column' => 'uuid',
        ],
        'cookieId' => [
            'table' => 'requests',
            'column' => 'cookie_id',
        ],
        'userId' => [
            'table' => 'requests',
            'column' => 'user_id',
        ],
        'urlProtocol' => [
            'table' => 'url_protocols',
            'column' => 'url_protocol',
        ],
        'urlDomain' => [
            'table' => 'url_domains',
            'column' => 'url_domain',
        ],
        'urlPath' => [
            'table' => 'url_paths',
            'column' => 'url_path',
        ],
        'urlQuery' => [
            'table' => 'url_queries',
            'column' => 'url_query',
        ],
        'method' => [
            'table' => 'methods',
            'column' => 'method',
        ],
        'routeName' => [
            'table' => 'route_names',
            'column' => 'route_name',
        ],
        'routeAction' => [
            'table' => 'route_actions',
            'column' => 'route_action',
        ],
        'deviceKind' => [
            'table' => 'device_kinds',
            'column' => 'device_kind',
        ],
        'deviceModel' => [
            'table' => 'device_models',
            'column' => 'device_model',
        ],
        'devicePlatform' => [
            'table' => 'device_platforms',
            'column' => 'device_platform',
        ],
        'deviceVersion' => [
            'table' => 'device_versions',
            'column' => 'device_version',
        ],
        'deviceIsMobile' => [
            'table' => 'requests',
            'column' => 'device_is_mobile',
        ],
        'agentString' => [
            'table' => 'agent_strings',
            'column' => 'agent_string',
        ],
        'agentBrowser' => [
            'table' => 'agent_browsers',
            'column' => 'agent_browser',
        ],
        'agentBrowserVersion' => [
            'table' => 'agent_browser_versions',
            'column' => 'agent_browser_version',
        ],
        'isRobot' => [
            'table' => 'requests',
            'column' => 'is_robot',
        ],
        'refererUrlProtocol' => [
            'table' => 'url_protocols',
            'column' => 'url_protocol',
        ],
        'refererUrlDomain' => [
            'table' => 'url_domains',
            'column' => 'url_domain',
        ],
        'refererUrlPath' => [
            'table' => 'url_paths',
            'column' => 'url_path',
        ],
        'refererUrlQuery' => [
            'table' => 'url_queries',
            'column' => 'url_query',
        ],
        'languagePreference' => [
            'table' => 'language_preferences',
            'column' => 'language_preference',
        ],
        'languageRange' => [
            'table' => 'language_ranges',
            'column' => 'language_range',
        ],
        'ipAddress' => [
            'table' => 'ip_addresses',
            'column' => 'ip_address',
        ],
        'ipLatitude' => [
            'table' => 'ip_latitudes',
            'column' => 'ip_latitude',
        ],
        'ipLongitude' => [
            'table' => 'ip_longitudes',
            'column' => 'ip_longitude',
        ],
        'ipCountryCode' => [
            'table' => 'ip_country_codes',
            'column' => 'ip_country_code',
        ],
        'ipCountryName' => [
            'table' => 'ip_country_names',
            'column' => 'ip_country_name',
        ],
        'ipRegion' => [
            'table' => 'ip_regions',
            'column' => 'ip_region',
        ],
        'ipCity' => [
            'table' => 'ip_cities',
            'column' => 'ip_city',
        ],
        'ipPostalZipCode' => [
            'table' => 'ip_postal_zip_codes',
            'column' => 'ip_postal_zip_code',
        ],
        'ipTimezone' => [
            'table' => 'ip_timezones',
            'column' => 'ip_timezone',
        ],
        'ipCurrency' => [
            'table' => 'ip_currencies',
            'column' => 'ip_currency',
        ],
        'responseStatusCode' => [
            'table' => 'response_status_codes',
            'column' => 'response_status_code',
        ],
        'responseDurationMs' => [
            'table' => 'requests',
            'column' => 'response_duration_ms',
        ],
        'requestedOn' => [
            'table' => 'requests',
            'column' => 'requested_on',
        ],
        'respondedOn' => [
            'table' => 'requests',
            'column' => 'responded_on',
        ],
    ];

    /**
     * @param Collection|RequestVO[] $requestVOs
     * @return Collection
     */
    public function storeRequests(Collection $requestVOs)
    {
        // first do all the linked data
        $isSqlLite = $this->databaseManager
                ->connection(config('railtracker.database_connection_name'))
                ->getDriverName() == 'sqlite';

        $builder = new BulkInsertOrUpdateBuilder(
            $this->databaseManager->connection(config('railtracker.database_connection_name')),
            new BulkInsertOrUpdateMySqlGrammar()
        );

        foreach (self::$requestAttributeTableColumnMap as $attribute => $tableAndColumn) {
            if ($tableAndColumn['table'] == 'requests') {
                continue;
            }

            $attributes = $requestVOs->pluck($attribute)->unique();

            // create the bulk update array
            $dataToInsert = [];

            foreach ($attributes as $attribute) {
                $dataToInsert[] = [
                    $tableAndColumn['column'] => $attribute,
                ];
            }

            if (!empty($dataToInsert)) {

                // we need a use case here since sqlite doesn't support update on duplicate key update
                if (!$isSqlLite) {
                    $builder->from(config('railtracker.table_prefix') . $tableAndColumn['table'])
                        ->insertOrUpdate($dataToInsert);
                }
                else {
                    $this->databaseManager->connection(config('railtracker.database_connection_name'))
                        ->transaction(
                            function () use ($tableAndColumn, $dataToInsert) {

                                foreach ($dataToInsert as $columnValues) {
                                    try {
                                        $this->databaseManager->connection(config('railtracker.database_connection_name'))
                                            ->table(config('railtracker.table_prefix') . $tableAndColumn['table'])
                                            ->insert($columnValues);
                                    } catch (\Exception $exception) {
                                    }
                                }

                            }
                        );
                }
            }
        }

        $bulkInsertData = [];

        /**
         * @var $requestVOs RequestVO[]
         */
        foreach ($requestVOs as $requestVO) {
            $bulkInsertData[] = $requestVO->returnArrayForDatabaseInteraction();
        }

        foreach(array_chunk($bulkInsertData, 50) as $chunkOfBulkInsertData){
            // then populate the requests table
            if (!empty($chunkOfBulkInsertData)) {
                $builder->from(config('railtracker.table_prefix') . 'requests')
                    ->insert($chunkOfBulkInsertData);
            }
        }

        $uuids = array_column($chunkOfBulkInsertData ?? [], 'uuid');

        /* is this ok? */
        $presumablyCreatedRows = $builder
                ->from(config('railtracker.table_prefix') . 'requests')
                ->select()
                ->whereIn('uuid', $uuids)
                ->get();

        return $presumablyCreatedRows ?? new Collection();
    }

    /**
     * @param Collection|RequestVO[] $requestVOs
     * @return Collection|RequestVO[] $requestVOs
     */
    public function removeDuplicateVOs(Collection &$requestVOs)
    {
        $existingRequests = $this->databaseManager->connection(config('railtracker.database_connection_name'))
            ->table(config('railtracker.table_prefix') . 'requests')
            ->whereIn('uuid', $requestVOs->pluck('uuid')->toArray())
            ->get(['uuid'])
            ->keyBy('uuid');

        $requestVOs = $requestVOs->filter(
            function (RequestVO $candidate) use ($existingRequests) {
                return !isset($existingRequests[$candidate->uuid]);
            }
        );

        /*
         * Filter out the requests without response data if one exists with response data
         *
         * Why: successful requests will have two RequestVOs; the one from before the response was generated, and one
         * from after. If response generation was successful (app didn't crash let's say), then we want to use the
         * RequestVO that has the response data in it, not the earlier version that is now obsolete.
         */
        $requestVOs = $requestVOs->filter(
            function (RequestVO $candidate) use ($requestVOs) {

                $respondedOnIsSetOnCandidate = !empty($candidate->respondedOn);

                if ($respondedOnIsSetOnCandidate) {
                    return true;
                }

                $respondedOnSetAndUuidMatchesCandidate =
                    $requestVOs->where('respondedOn', '!=', null)->where('uuid', $candidate->uuid);

                $noMatchThusUseCurrent = $respondedOnSetAndUuidMatchesCandidate->count() == 0;

                /*
                 * If there is no match, then the one we're currently looking at the is the only one with this uuid, and
                 * thus we want to use it.
                 *
                 * If there *is* a match then we return false because we want to discard this one and use the one with
                 * the respondedOn set on it.
                 */

                return $noMatchThusUseCurrent;
            }
        );

        return $requestVOs;
    }

    /**
     * @param Collection $requestVOs
     * @return Collection
     */
    public function getMostRecentRequestForEachIpAddress($requestVOs)
    {
        foreach($requestVOs as $requestVO){
            $ipAddresses[] = $requestVO->ipAddress;
        }

        $matchingRequests = $this->databaseManager->connection(config('railtracker.database_connection_name'))
            ->table(config('railtracker.table_prefix') . 'requests')
            ->whereIn('ip_address', $ipAddresses)
            ->orderBy('requested_on', 'desc')
            ->groupBy('ip_address')
            ->get();

        return $matchingRequests;
    }
}