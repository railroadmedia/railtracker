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

    private static $rowsToInsertByTable = [
        'url_protocols' => [
            ['url_protocol' => 'urlProtocol'],
            ['url_protocol' => 'refererUrlProtocol'],
        ],
        'url_domains' => [
            ['url_domain' => 'urlDomain'],
            ['url_domain' => 'refererUrlDomain'],
        ],
        'url_paths' => [
            ['url_path' => 'urlPath'],
            ['url_path' => 'refererUrlPath'],
        ],
        'methods' => [
            ['method' => 'method'],
        ],
        'route_names' => [
            ['route_name' => 'routeName'],
        ],
        'device_kinds' => [
            ['device_kind' => 'deviceKind'],
        ],
        'device_models' => [
            ['device_model' => 'deviceModel'],
        ],
        'device_platforms' => [
            ['device_platform' => 'devicePlatform'],
        ],
        'device_versions' => [
            ['device_version' => 'deviceVersion'],
        ],
        'agent_browsers' => [
            ['agent_browser' => 'agentBrowser'],
        ],
        'agent_browser_versions' => [
            ['agent_browser_version' => 'agentBrowserVersion'],
        ],
        'language_preferences' => [
            ['language_preference' => 'languagePreference'],
        ],
        'language_ranges' => [
            ['language_range' => 'languageRange'],
        ],
        'ip_addresses' => [
            ['ip_address' => 'ipAddress'],
        ],
        'ip_latitudes' => [
            ['ip_latitude' => 'ipLatitude'],
        ],
        'ip_longitudes' => [
            ['ip_longitude' => 'ipLongitude'],
        ],
        'ip_country_codes' => [
            ['ip_country_code' => 'ipCountryCode'],
        ],
        'ip_country_names' => [
            ['ip_country_name' => 'ipCountryName'],
        ],
        'ip_regions' => [
            ['ip_region' => 'ipRegion'],
        ],
        'ip_cities' => [
            ['ip_city' => 'ipCity'],
        ],
        'ip_postal_zip_codes' => [
            ['ip_postal_zip_code' => 'ipPostalZipCode'],
        ],
        'ip_timezones' => [
            ['ip_timezone' => 'ipTimezone'],
        ],
        'ip_currencies' => [
            ['ip_currency' => 'ipCurrency'],
        ],
        'response_status_codes' => [
            ['response_status_code' => 'responseStatusCode'],
        ],
        'response_durations' => [
            ['response_duration_ms' => 'responseDurationMs'],
        ],
        'exception_codes' => [
            ['exception_code' => 'exceptionCode'],
        ],
        'exception_lines' => [
            ['exception_line' => 'exceptionLine'],
        ],

        // long strings requiring hashes

        'url_queries' => [ // table
            [ // a row
                'url_query' => 'urlQuery',
                'url_query_hash' => 'urlQueryHash',
            ],
            [ // another row
                'url_query' => 'refererUrlQuery',
                'url_query_hash' => 'refererUrlQueryHash',
            ],
        ],

        'route_actions' => [
            [
                'route_action' => 'routeAction',
                'route_action_hash' => 'routeActionHash',
            ],
        ],
        'agent_strings' => [
            [
                'agent_string' => 'agentString',
                'agent_string_hash' => 'agentStringHash',
            ],
        ],
        'exception_classes' => [
            [
                'exception_class' => 'exceptionClass',
                'exception_class_hash' => 'exceptionClassHash',
            ],
        ],
        'exception_files' => [
            [
                'exception_file' => 'exceptionFile',
                'exception_file_hash' => 'exceptionFileHash',
            ],
        ],
        'exception_messages' => [
            [
                'exception_message' => 'exceptionMessage',
                'exception_message_hash' => 'exceptionMessageHash',
            ],
        ],
        'exception_traces' => [
            [
                'exception_trace' => 'exceptionTrace',
                'exception_trace_hash' => 'exceptionTraceHash',
            ],
        ],
    ];

    /**
     * @param Collection|RequestVO[] $requestVOs
     * @return Collection
     */
    public function storeRequests(Collection $requestVOs)
    {
        $dbConnectionName = config('railtracker.database_connection_name');

        // MySQL supports update-on-duplicate-key-update, SQLite does not
        $isMySql = $this->databaseManager->connection($dbConnectionName)->getDriverName() == 'mysql';

        // --------- Part 1: linked data ---------

        $builder = new BulkInsertOrUpdateBuilder(
            $this->databaseManager->connection($dbConnectionName),
            new BulkInsertOrUpdateMySqlGrammar()
        );

        foreach (self::$rowsToInsertByTable as $table => $rowsToInsert) {
            $dataToInsert = [];

            foreach($requestVOs as $requestVO){

                foreach($rowsToInsert as $mappings){
                    $row = [];
                    foreach($mappings as $column => $property){
                        /*
                         * empty(0) returns true, but 0 is valid exception code, thus here explicitly allow
                         */
                        $specialException = ($table === 'exception_codes') && ($requestVO->$property === 0);

                        if(!empty($requestVO->$property) || $specialException){
                            $row[$column] = $requestVO->$property;
                        }
                    }
                    if(!empty($row)){
                        $dataToInsert[] = $row;
                    }
                }
            }

            if(empty($dataToInsert)) continue;

            if ($isMySql) {
                try{
                    $builder->from(config('railtracker.table_prefix') . $table)
                        ->insertOrUpdate($dataToInsert);
                }catch(\Exception $e){
                    error_log($e);
                    dump('Error while writing to association tables ("' . $e->getMessage() . '")');
                }
            } else {  // need use-case here since sqlite doesn't support update on duplicate key update
                foreach ($dataToInsert as $columnValues) {
                    try {
                        $results = $this->databaseManager->connection($dbConnectionName)
                            ->table(config('railtracker.table_prefix') . $table)
                            ->where($columnValues)
                            ->get();
                        if($results->isEmpty()){
                            $this->databaseManager->connection($dbConnectionName)
                                ->table(config('railtracker.table_prefix') . $table)
                                ->insert($columnValues);
                        }
                    } catch (\Exception $e) {
                        error_log($e);
                        dump('Error while writing to association tables ("' . $e->getMessage() . '")');
                    }
                }
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

        $table = config('railtracker.table_prefix') . 'requests';

        foreach(array_chunk($bulkInsertData, self::$BULK_INSERT_CHUNK_SIZE) as $chunkOfBulkInsertData){

            if (empty($chunkOfBulkInsertData)) continue;

            try{
                if ($isMySql) {
                    $builder->from($table)->insertOrUpdate($chunkOfBulkInsertData);
                }else{
                    $builder->from($table)->insert($chunkOfBulkInsertData);
                }
            }catch(\Exception $e){
                error_log($e);
                dump('Error while writing to requests table ("' . $e->getMessage() . '")');
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
        $ipAddresses = [];

        $table = config('railtracker.table_prefix') . 'requests';
        $dbConnectionName = config('railtracker.database_connection_name');

        foreach($requestVOs as $requestVO){
            $ipAddresses[] = $requestVO->ipAddress;
        }

        if(empty($ipAddresses)){
            return collect([]);
        }

        $matchingRequests = $this->databaseManager->connection($dbConnectionName)
            ->table($table)
            ->select(
                [
                    $this->databaseManager->raw('MAX(requested_on) as requested_on'),
                    $this->databaseManager->raw('MAX(ip_address) as ip_address'),
                    $this->databaseManager->raw('MAX(ip_latitude) as ip_latitude'),
                    $this->databaseManager->raw('MAX(ip_longitude) as ip_longitude'),
                    $this->databaseManager->raw('MAX(ip_country_code) as ip_country_code'),
                    $this->databaseManager->raw('MAX(ip_country_name) as ip_country_name'),
                    $this->databaseManager->raw('MAX(ip_region) as ip_region'),
                    $this->databaseManager->raw('MAX(ip_city) as ip_city'),
                    $this->databaseManager->raw('MAX(ip_postal_zip_code) as ip_postal_zip_code'),
                    $this->databaseManager->raw('MAX(ip_timezone) as ip_timezone'),
                    $this->databaseManager->raw('MAX(ip_currency) as ip_currency'),
                ]
            )
            ->whereIn('ip_address', $ipAddresses)
            ->groupBy(['ip_address'])
            ->get();

        return $matchingRequests;
    }
}