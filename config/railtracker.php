<?php

return [
    'global_is_active' => true,

    'cache_duration' => 60 * 60 * 24 * 30,
    'database_connection_name' => 'mysql',

    'tables' => [
        'url_protocols' => 'railtracker_url_protocols',
        'url_domains' => 'railtracker_url_domains',
        'url_paths' => 'railtracker_url_paths',
        'url_queries' => 'railtracker_url_queries',
        'urls' => 'railtracker_urls',
        'routes' => 'railtracker_routes',
        'request_methods' => 'railtracker_request_methods',
        'request_agents' => 'railtracker_request_agents',
        'request_devices' => 'railtracker_request_devices',
        'geoip' => 'railtracker_geoip',
        'request_languages' => 'railtracker_request_languages',
        'requests' => 'railtracker_requests',
        'responses' => 'railtracker_responses',
        'response_status_codes' => 'railtracker_response_status_codes',
        'exceptions' => 'railtracker_exceptions',
        'request_exceptions' => 'railtracker_request_exceptions',
        'media_playback_types' => 'railtracker_media_playback_types',
        'media_playback_sessions' => 'railtracker_media_playback_sessions',
    ],
    'requestExclusionPaths'=> ['members/are-we-live-poll'],
];