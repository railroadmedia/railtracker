<?php

namespace Railroad\Railtracker\Repositories;

use Illuminate\Support\Collection;
use Railroad\Railtracker\QueryBuilders\BulkInsertOrUpdateBuilder;
use Railroad\Railtracker\QueryBuilders\BulkInsertOrUpdateMySqlGrammar;
use Railroad\Railtracker\QueryBuilders\BulkInsertOrUpdateSqlLiteGrammar;
use Railroad\Railtracker\ValueObjects\RequestVO;
use Throwable;

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
            'table' => 'referer_url_protocols',
            'column' => 'referer_url_protocol',
        ],
        'refererUrlDomain' => [
            'table' => 'referer_url_domains',
            'column' => 'referer_url_domain',
        ],
        'refererUrlPath' => [
            'table' => 'referer_url_paths',
            'column' => 'referer_url_path',
        ],
        'refererUrlQuery' => [
            'table' => 'referer_url_queries',
            'column' => 'referer_url_query',
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
     * @throws Throwable
     */
    public function storeRequests(Collection $requestVOs)
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
                                    $this->databaseManager->connection(config('railtracker.database_connection_name'))
                                        ->table(config('railtracker.table_prefix') . $tableAndColumn['table'])
                                        ->updateOrInsert($columnValues);
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
            $bulkInsertData[] = [
                'uuid' => $requestVO->uuid,
                'cookie_id' => $requestVO->cookieId,
                'user_id' => $requestVO->userId,
                'url_protocol' => $requestVO->urlProtocol,
                'url_domain' => $requestVO->urlDomain,
                'url_path' => $requestVO->urlPath,
                'url_query' => $requestVO->urlQuery,
                'method' => $requestVO->method,
                'route_name' => $requestVO->routeName,
                'route_action' => $requestVO->routeAction,
                'device_kind' => $requestVO->deviceKind,
                'device_model' => $requestVO->deviceModel,
                'device_platform' => $requestVO->devicePlatform,
                'device_version' => $requestVO->deviceVersion,
                'device_is_mobile' => $requestVO->deviceIsMobile,
                'agent_string' => $requestVO->agentString,
                'agent_browser' => $requestVO->agentBrowser,
                'agent_browser_version' => $requestVO->agentBrowserVersion,
                'referer_url_protocol' => $requestVO->refererUrlProtocol,
                'referer_url_domain' => $requestVO->refererUrlDomain,
                'referer_url_path' => $requestVO->refererUrlPath,
                'referer_url_query' => $requestVO->refererUrlQuery,
                'language_preference' => $requestVO->languagePreference,
                'language_range' => $requestVO->languageRange,
                'ip_address' => $requestVO->ipAddress,
                'ip_latitude' => $requestVO->ipLatitude,
                'ip_longitude' => $requestVO->ipLongitude,
                'ip_country_code' => $requestVO->ipCountryCode,
                'ip_country_name' => $requestVO->ipCountryName,
                'ip_region' => $requestVO->ipRegion,
                'ip_city' => $requestVO->ipCity,
                'ip_postal_zip_code' => $requestVO->ipPostalZipCode,
                'ip_timezone' => $requestVO->ipTimezone,
                'ip_currency' => $requestVO->ipCurrency,
                'is_robot' => $requestVO->isRobot,
                'response_status_code' => $requestVO->responseStatusCode,
                'response_duration_ms' => $requestVO->responseDurationMs,
                'requested_on' => $requestVO->requestedOn,
                'responded_on' => $requestVO->respondedOn,
            ];
        }

        // then populate the requests table
        if (!empty($bulkInsertData)) {
            $builder->from(config('railtracker.table_prefix') . 'requests')
                ->insert($bulkInsertData);
        }
    }
}