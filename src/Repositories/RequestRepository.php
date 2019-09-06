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
        'url_protocols' => [
            [
                'property' => 'urlProtocol',
                'column' => 'url_protocol'
            ],
            [
                'property' => 'refererUrlProtocol',
                'column' => 'url_protocol'
            ]
        ],
        'url_domains' => [
            [
                'property' => 'urlDomain',
                'column' => 'url_domain'
            ],
            [
                'property' => 'refererUrlDomain',
                'column' => 'url_domain'
            ]
        ],
        'url_paths' => [
            [
                'property' => 'urlPath',
                'column' => 'url_path'
            ],
            [
                'property' => 'refererUrlPath',
                'column' => 'url_path'
            ]
        ],
        'methods' => [
            [
                'property' => 'method',
                'column' => 'method'
            ]
        ],
        'route_names' => [
            [
                'property' => 'routeName',
                'column' => 'route_name'
            ]
        ],
        'device_kinds' => [
            [
                'property' => 'deviceKind',
                'column' => 'device_kind'
            ]
        ],
        'device_models' => [
            [
                'property' => 'deviceModel',
                'column' => 'device_model'
            ]
        ],
        'device_platforms' => [
            [
                'property' => 'devicePlatform',
                'column' => 'device_platform'
            ]
        ],
        'device_versions' => [
            [
                'property' => 'deviceVersion',
                'column' => 'device_version'
            ]
        ],
        'agent_browsers' => [
            [
                'property' => 'agentBrowser',
                'column' => 'agent_browser'
            ]
        ],
        'agent_browser_versions' => [
            [
                'property' => 'agentBrowserVersion',
                'column' => 'agent_browser_version'
            ]
        ],
        'language_preferences' => [
            [
                'property' => 'languagePreference',
                'column' => 'language_preference'
            ]
        ],
        'language_ranges' => [
            [
                'property' => 'languageRange',
                'column' => 'language_range'
            ]
        ],
        'ip_addresses' => [
            [
                'property' => 'ipAddress',
                'column' => 'ip_address'
            ]
        ],
        'ip_latitudes' => [
            [
                'property' => 'ipLatitude',
                'column' => 'ip_latitude'
            ]
        ],
        'ip_longitudes' => [
            [
                'property' => 'ipLongitude',
                'column' => 'ip_longitude'
            ]
        ],
        'ip_country_codes' => [
            [
                'property' => 'ipCountryCode',
                'column' => 'ip_country_code'
            ]
        ],
        'ip_country_names' => [
            [
                'property' => 'ipCountryName',
                'column' => 'ip_country_name'
            ]
        ],
        'ip_regions' => [
            [
                'property' => 'ipRegion',
                'column' => 'ip_region'
            ]
        ],
        'ip_cities' => [
            [
                'property' => 'ipCity',
                'column' => 'ip_city'
            ]
        ],
        'ip_postal_zip_codes' => [
            [
                'property' => 'ipPostalZipCode',
                'column' => 'ip_postal_zip_code'
            ]
        ],
        'ip_timezones' => [
            [
                'property' => 'ipTimezone',
                'column' => 'ip_timezone'
            ]
        ],
        'ip_currencies' => [
            [
                'property' => 'ipCurrency',
                'column' => 'ip_currency'
            ]
        ],
        'response_status_codes' => [
            [
                'property' => 'responseStatusCode',
                'column' => 'response_status_code'
            ]
        ],
        'response_durations' => [
            [
                'property' => 'responseDurationMs',
                'column' => 'response_duration_ms'
            ]
        ],
        'exception_codes' => [
            [
                'property' => 'exceptionCode',
                'column' => 'exception_code'
            ]
        ],
        'exception_lines' => [
            [
                'property' => 'exceptionLine',
                'column' => 'exception_line'
            ]
        ],

        // long strings requiring hashes

        'url_queries' => [
            [
                'property' => 'urlQuery',
                'column' => 'url_query',
            ],
            [
                'property' => 'urlQueryHash',
                'column' => 'url_query_hash',
            ],
            [
                'property' => 'refererUrlQuery',
                'column' => 'url_query'
            ],
            [
                'property' => 'refererUrlQueryHash',
                'column' => 'url_query_hash'
            ],
        ],
        'route_actions' => [
            [
                'property' => 'routeAction',
                'column' => 'route_action'
            ],
            [
                'property' => 'routeActionHash',
                'column' => 'route_action_hash',
            ],
        ],
        'agent_strings' => [
            [
                'property' => 'agentString',
                'column' => 'agent_string'
            ],
            [
                'property' => 'agentStringHash',
                'column' => 'agent_string_hash',
            ],
        ],
        'exception_classes' => [
            [
                'property' => 'exceptionClass',
                'column' => 'exception_class'
            ],
            [
                'property' => 'exceptionClassHash',
                'column' => 'exception_class_hash',
            ],
        ],
        'exception_files' => [
            [
                'property' => 'exceptionFile',
                'column' => 'exception_file'
            ],
            [
                'property' => 'exceptionFileHash',
                'column' => 'exception_file_hash',
            ],
        ],
        'exception_messages' => [
            [
                'property' => 'exceptionMessage',
                'column' => 'exception_message'
            ],
            [
                'property' => 'exceptionMessageHash',
                'column' => 'exception_message_hash',
            ],
        ],
        'exception_traces' => [
            [
                'property' => 'exceptionTrace',
                'column' => 'exception_trace'
            ],
            [
                'property' => 'exceptionTraceHash',
                'column' => 'exception_trace_hash',
            ],
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

        foreach (self::$requestAttributeTableColumnMap as $table => $propertiesAndColumns) {

            $dataToInsert = $requestVOs->map(function($requestV0) use ($propertiesAndColumns){
                foreach($propertiesAndColumns as $propertyAndColumn){
                    $property = $propertyAndColumn['property'];
                    $column = $propertyAndColumn['column'];
                    $tableSpecificPropertiesFromOneRequest[$column] = $requestV0->$property;
                }
                return $tableSpecificPropertiesFromOneRequest ?? [];
            })->toArray();

            if(empty($dataToInsert)) continue;

            if (!$isSqlLite) { // need use-case here since sqlite doesn't support update on duplicate key update
                try{
                    $builder->from(config('railtracker.table_prefix') . $table)
                        ->insertOrUpdate($dataToInsert);
                }catch(\Exception $e){
                    error_log($e);
                    dump('Error while writing to association tables ("' . $e->getMessage() . '")');
                }
            } else {
                $this->databaseManager->connection($dbConnectionName)->transaction(
                    // todo: fix
//                    function () use ($tableAndColumn, $dataToInsert, $dbConnectionName) {
//                        foreach ($dataToInsert as $columnValues) {
//                            try {
//                                $this->databaseManager->connection($dbConnectionName)
//                                    ->table(config('railtracker.table_prefix') . $tableAndColumn['table'])
//                                    ->insert($columnValues);
//                            } catch (\Exception $e) {
//                                error_log($e);
//                                dump('Error while writing to association tables ("' . $e->getMessage() . '")');
//                            }
//                        }
//                    }
                );
            }
        }

        dd('PICK UP HERE'); // todo: pick up here
        dd('PICK UP HERE'); // todo: pick up here
        dd('PICK UP HERE'); // todo: pick up here
        dd('PICK UP HERE'); // todo: pick up here

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