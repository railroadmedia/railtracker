<?php

namespace Railroad\Railtracker\Repositories;

use Illuminate\Support\Collection;
use Railroad\Railtracker\QueryBuilders\BulkInsertOrUpdateBuilder;
use Railroad\Railtracker\QueryBuilders\BulkInsertOrUpdateMySqlGrammar;
use Railroad\Railtracker\QueryBuilders\BulkInsertOrUpdateSqlLiteGrammar;
use Railroad\Railtracker\ValueObjects\RequestVO;

class RequestRepository extends TrackerRepositoryBase
{
    private static $BULK_INSERT_CHUNK_SIZE = 20;

    private static $requestAttributeTableColumnMap = [
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
        'method' => [
            'table' => 'methods',
            'column' => 'method',
        ],
        'routeName' => [
            'table' => 'route_names',
            'column' => 'route_name',
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
        'agentBrowser' => [
            'table' => 'agent_browsers',
            'column' => 'agent_browser',
        ],
        'agentBrowserVersion' => [
            'table' => 'agent_browser_versions',
            'column' => 'agent_browser_version',
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
            'table' => 'response_durations',
            'column' => 'response_duration_ms',
        ],
        'exceptionCode' => [
            'table' => 'exception_codes',
            'column' => 'exception_code'
        ],
        'exceptionLine' => [
            'table' => 'exception_lines',
            'column' => 'exception_line'
        ],

        // hashed long strings

        'urlQueryHash' => [
            'table' => 'url_queries',
            'column' => 'url_query_hash'
        ],
        'refererUrlQueryHash' => [
            'table' => 'url_queries',
            'column' => 'url_query_hash'
        ],
        'routeActionHash' => [
            'table' => 'route_actions',
            'column' => 'route_action_hash'
        ],
        'agentStringHash' => [
            'table' => 'agent_strings',
            'column' => 'agent_string_hash'
        ],
        'exceptionClassHash' => [
            'table' => 'exception_classes',
            'column' => 'exception_class_hash'
        ],
        'exceptionFileHash' => [
            'table' => 'exception_files',
            'column' => 'exception_file_hash'
        ],
        'exceptionMessageHash' => [
            'table' => 'exception_messages',
            'column' => 'exception_message_hash'
        ],
        'exceptionTraceHash' => [
            'table' => 'exception_traces',
            'column' => 'exception_trace_hash'
        ],
    ];

    /**
     * @param Collection|RequestVO[] $requestVOs
     * @return Collection
     */
    public function storeRequests(Collection $requestVOs)
    {
        $table = config('railtracker.table_prefix') . 'requests'; // todo: a more proper way to get this?
        $dbConnectionName = config('railtracker.database_connection_name');

        // --------- Part 1: linked data ---------

        $isSqlLite = $this->databaseManager
                ->connection($dbConnectionName)
                ->getDriverName() == 'sqlite';

        $builder = new BulkInsertOrUpdateBuilder(
            $this->databaseManager->connection($dbConnectionName),
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
                if(!is_null($attribute)){
                    $dataToInsert[] = [$tableAndColumn['column'] => $attribute];
                }
            }

            if(empty($dataToInsert)){
                continue;
            }

            if (!empty($dataToInsert)) {

//                if (!$isSqlLite) { // need use-case here since sqlite doesn't support update on duplicate key update
                    try{
                        $builder->from(config('railtracker.table_prefix') . $tableAndColumn['table'])
                            ->insertOrUpdate($dataToInsert);
                    }catch(\Exception $e){
                        error_log($e);
                        dump('Error while writing to association tables ("' . $e->getMessage() . '")');
                    }
//                } else {
//                    $this->databaseManager->connection($dbConnectionName)->transaction(
//                        function () use ($tableAndColumn, $dataToInsert, $dbConnectionName) {
//                            foreach ($dataToInsert as $columnValues) {
//                                try {
//                                    $this->databaseManager->connection($dbConnectionName)
//                                        ->table(config('railtracker.table_prefix') . $tableAndColumn['table'])
//                                        ->insert($columnValues);
//                                } catch (\Exception $e) {
//                                    error_log($e);
//                                    dump('Error while writing to association tables ("' . $e->getMessage() . '")');
//                                }
//                            }
//                        }
//                    );
//                }
            }
        }

        // --------- Part 2: populate requests table ---------

        $bulkInsertData = [];

        /**
         * @var $requestVOs RequestVO[]
         */
        foreach ($requestVOs as $requestVO) {
            $bulkInsertData[] = $requestVO->returnArrayForDatabaseInteraction();
        }

        foreach(array_chunk($bulkInsertData, self::$BULK_INSERT_CHUNK_SIZE) as $chunkOfBulkInsertData){

            if (!empty($chunkOfBulkInsertData)) {
                try{
                    $builder->from($table)->insertOrUpdate($chunkOfBulkInsertData);
                }catch(\Exception $e){
                    error_log($e);
                    dump('Error while writing to requests table ("' . $e->getMessage() . '")');
                }
            }
        }

        $uuids = array_column($chunkOfBulkInsertData ?? [], 'uuid');

        // because we cant' get created rows from insert, it seems
        $presumablyCreatedRows = $builder
                ->from($table)
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
        $table = config('railtracker.table_prefix') . 'requests';
        $dbConnectionName = config('railtracker.database_connection_name');

        $existingRequests = $this->databaseManager->connection($dbConnectionName)
            ->table($table)
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
        $table = config('railtracker.table_prefix') . 'requests';
        $dbConnectionName = config('railtracker.database_connection_name');

        foreach($requestVOs as $requestVO){
            $ipAddresses[] = $requestVO->ipAddress;
        }

        $matchingRequests = $this->databaseManager->connection($dbConnectionName)
            ->table($table)
            ->whereIn('ip_address', $ipAddresses)
            ->orderBy('requested_on', 'desc')
            ->groupBy('ip_address')
            ->get();

        return $matchingRequests;
    }
}