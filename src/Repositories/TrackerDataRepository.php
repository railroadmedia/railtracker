<?php

namespace Railroad\Railtracker\Repositories;

use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;

class TrackerDataRepository
{
    /**
     * @var DatabaseManager
     */
    private $databaseManager;

    /**
     * TrackerDataRepository constructor.
     */
    public function __construct(DatabaseManager $databaseManager)
    {
        $this->databaseManager = $databaseManager;
    }

    public function getRequestsForUser(
        $userId,
        $limit = 25,
        $skip = 0,
        $orderByColumn = 'railtracker_requests.requested_on',
        $orderByDirection = 'desc'
    ) {
        $rows = $this->databaseManager->connection()->table('railtracker_requests')
            ->join(
                'railtracker_urls',
                'railtracker_urls.id',
                '=',
                'railtracker_requests.url_id'
            )
            ->join(
                'railtracker_url_protocols',
                'railtracker_url_protocols.id',
                '=',
                'railtracker_urls.protocol_id'
            )
            ->join(
                'railtracker_url_domains',
                'railtracker_url_domains.id',
                '=',
                'railtracker_urls.domain_id'
            )
            ->leftJoin(
                'railtracker_url_paths',
                'railtracker_url_paths.id',
                '=',
                'railtracker_urls.path_id'
            )
            ->leftJoin(
                'railtracker_url_queries',
                'railtracker_url_queries.id',
                '=',
                'railtracker_urls.query_id'
            )
            ->leftJoin(
                'railtracker_routes',
                'railtracker_routes.id',
                '=',
                'railtracker_requests.route_id'
            )
            ->join(
                'railtracker_request_agents',
                'railtracker_request_agents.id',
                '=',
                'railtracker_requests.agent_id'
            )
            ->join(
                'railtracker_request_devices',
                'railtracker_request_devices.id',
                '=',
                'railtracker_requests.device_id'
            )
            ->join(
                'railtracker_request_languages',
                'railtracker_request_languages.id',
                '=',
                'railtracker_requests.language_id'
            )
            ->select(
                [
                    'railtracker_requests.id as id',
                    'railtracker_requests.uuid as uuid',
                    'railtracker_url_protocols.protocol as protocol',
                    'railtracker_url_domains.name as domain',
                    'railtracker_url_paths.path as path',
                    'railtracker_url_queries.string as query',
                    'railtracker_routes.name as route_name',
                    'railtracker_routes.action as route_action',
                    'railtracker_request_agents.name as agent',
                    'railtracker_request_agents.browser as agent_browser',
                    'railtracker_request_agents.browser_version as agent_browser_version',
                    'railtracker_request_devices.kind as device_type',
                    'railtracker_request_devices.model as device_model',
                    'railtracker_request_devices.platform as device_platform',
                    'railtracker_request_devices.platform_version as device_platform_version',
                    'railtracker_request_devices.is_mobile as device_is_mobile',
                    'railtracker_request_languages.preference as language_preference',
                    'railtracker_request_languages.language_range as language_range',
                ]
            )
            ->where('railtracker_requests.user_id', '=', $userId)
            ->limit($limit)
            ->skip($skip)
            ->orderBy($orderByColumn, $orderByDirection)
            ->get();

        return $this->collectionToMultiDimensionalArray($rows);
    }

    /**
     * @param Collection $collection
     * @return array
     */
    private function collectionToMultiDimensionalArray(Collection $collection)
    {
        return $collection->map(
            function ($x) {
                return (array)$x;
            }
        )->toArray();
    }
}