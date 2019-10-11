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

    'table_prefix' => 'railtracker3_',
    'table_prefix_media_playback_tracking' => 'railtracker3_',
    'media_playback_types_table' => 'media_playback_types',
    'media_playback_sessions_table' => 'media_playback_sessions',

    // cache
    'redis_host' => 'redis',
    'redis_port' => 6379,
    'cache_duration' => 60 * 60 * 24 * 30,
    'batch_prefix' => env('RAILTRACKER_BATCH_PREFIX', 'railtracker_batch_'),

    // exclude request paths
    'exclusion_regex_paths'=> ['/media\-playback\-tracking\/media\-playback\-session*/'],

    // route middleware group
    'route_middleware_logged_in_groups' => 'web',

    // ip_data_api
    'ip_data_api_key' => env('IP_DATA_API_KEY')

    // toggle to test ip-data api on local
    //'test-ip' => '108.172.176.221',
    //'test-ip' => '129.80.245.100',
    //'test-ip' => '52.152.91.149',
    //'test-ip' => '156.184.155.130',
];
