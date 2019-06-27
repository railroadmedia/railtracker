<?php

return [
    'global_is_active' => true,

    // database
    'database_connection_name' => 'mysql',
    'database_name' => 'mydb',
    'database_user' => 'root',
    'database_password' => 'root',
    'database_host' => 'mysql',
    'database_driver' => 'pdo_mysql',
    'database_in_memory' => false,
    'enable_query_log' => false,
    'enable_query_log_dumper' => false,

    'data_mode' => 'host', // 'host' or 'client', hosts do the db migrations, clients do not

    // cache
    'redis_host' => 'redis',
    'redis_port' => 6379,
    'cache_duration' => 60 * 60 * 24 * 30,

    'batch-prefix' => env('RAILTRACKER_BATCH_PREFIX', 'railtracker_batch_'),

    // entities
    'entities' => [
        [
            'path' => __DIR__ . '/../src/Entities',
            'namespace' => 'Railroad\Railtracker\Entities',
        ],
    ],

    'exclusion_regex_paths'=> ['/media\-playback\-tracking\/media\-playback\-session*/'],

    // 'connection_mask_prefix' => '' // ?

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
    
    // route middleware group
    'route_middleware_logged_in_groups' => 'web',
];