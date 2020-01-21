<?php

namespace Railroad\Railtracker\Console\Commands;

use Exception;
use Illuminate\Cookie\CookieJar;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Railroad\Railtracker\Events\RequestTracked;
use Railroad\Railtracker\QueryBuilders\BulkInsertOrUpdateBuilder;
use Railroad\Railtracker\QueryBuilders\BulkInsertOrUpdateMySqlGrammar;
use Railroad\Railtracker\Repositories\RequestRepository;
use Railroad\Railtracker\Services\BatchService;
use Railroad\Railtracker\Services\IpDataApiSdkService;
use Railroad\Railtracker\Trackers\ExceptionTracker;
use Railroad\Railtracker\ValueObjects\ExceptionVO;
use Railroad\Railtracker\ValueObjects\RequestVO;

class LegacyMigrate extends \Illuminate\Console\Command
{
    /**
     * @var string
     */
    protected $description = 'Migrate data from legacy tables.';

    /**
     * @var string
     */
    protected $signature = 'legacyMigrate {--default}';

    /**
     * @var DatabaseManager
     */
    private $databaseManager;

    /**
     * @var RequestRepository
     */
    private $requestRepository;

    private $chunkSize = 100;
    private $limit = 100;

    private static $map = [
        'url_protocols' => [ // 'association table' => fields required in table for each property of legacy data
            ['url_protocol' => 'url_protocol'], // column-name => property name in JOINed data
            ['url_protocol' => 'url_referer_protocol'],
        ],
        'url_domains' => [
            ['url_domain' => 'url_name'],
            ['url_domain' => 'url_referer_name'],
        ],
        'url_paths' => [
            ['url_path' => 'url_path'],
            ['url_path' => 'url_referer_path'],
        ],
        'methods' => [
            ['method' => 'method_method'],
        ],
        'route_names' => [
            ['route_name' => 'route_action'],
        ],
        'device_kinds' => [
            ['device_kind' => 'device_kind'],
        ],
        'device_models' => [
            ['device_model' => 'device_model'],
        ],
        'device_platforms' => [
            ['device_platform' => 'device_platform'],
        ],
        'device_versions' => [
            ['device_version' => 'device_platform_version'],
        ],
        'agent_browsers' => [
            ['agent_browser' => 'agent_browser'],
        ],
        'agent_browser_versions' => [
            ['agent_browser_version' => 'agent_browser_version'],
        ],
        'language_preferences' => [
            ['language_preference' => 'language_preference'],
        ],
        'language_ranges' => [
            ['language_range' => 'language_language_range'],
        ],
        'ip_addresses' => [
            ['ip_address' => 'geoip_ip_address'],
        ],
        'ip_latitudes' => [
            ['ip_latitude' => 'geoip_latitude'],
        ],
        'ip_longitudes' => [
            ['ip_longitude' => 'geoip_longitude'],
        ],
        'ip_country_codes' => [
            ['ip_country_code' => 'geoip_country_code'],
        ],
        'ip_country_names' => [
            ['ip_country_name' => 'geoip_country_name'],
        ],
        'ip_regions' => [
            ['ip_region' => 'geoip_region'],
        ],
        'ip_cities' => [
            ['ip_city' => 'geoip_city'],
        ],
        'ip_postal_zip_codes' => [
            ['ip_postal_zip_code' => 'geoip_postal_code'],
        ],
        'ip_timezones' => [
            ['ip_timezone' => 'geoip_timezone'],
        ],
        'ip_currencies' => [
            ['ip_currency' => 'geoip_currency'],
        ],

        // todo: handle these separately because the JOINS are so expensive?

//        'response_status_codes' => [
//            ['response_status_code' => 'XXXXXXXXXXXXXXXXXXXXX'],
//        ],
//        'response_durations' => [
//            ['response_duration_ms' => 'XXXXXXXXXXXXXXXXXXXXX'],
//        ],
//        'exception_codes' => [
//            ['exception_code' => 'XXXXXXXXXXXXXXXXXXXXX'],
//        ],
//        'exception_lines' => [
//            ['exception_line' => 'XXXXXXXXXXXXXXXXXXXXX'],
//        ],

        // long strings requiring hashes

        'url_queries' => [ // table
            [ // a row
                'url_query' => 'url_query_string',
                'url_query_hash' => 'url_query_string_hash', // these are manually created in method "" below.
            ],
            [ // another row
                'url_query' => 'url_referer_query_string',
                'url_query_hash' => 'url_referer_query_string_hash', // these are manually created in method "" below.
            ],
        ],

        'route_actions' => [
            [
                'route_action' => 'route_action',
                'route_action_hash' => 'route_action_hash', // these are manually created in method "" below.
            ],
        ],
        'agent_strings' => [
            [
                'agent_string' => 'agent_name',
                'agent_string_hash' => 'agent_name_hash', // these are manually created in method "" below.
            ],
        ],
//        'exception_classes' => [
//            [
//                'exception_class' => 'exceptionClass',
//                //'exception_class_hash' => 'exceptionClassHash',  // todo: generate hash or get from legacy?
//            ],
//        ],
//        'exception_files' => [
//            [
//                'exception_file' => 'exceptionFile',
//                //'exception_file_hash' => 'exceptionFileHash',  // todo: generate hash or get from legacy?
//            ],
//        ],
//        'exception_messages' => [
//            [
//                'exception_message' => 'exceptionMessage',
//                //'exception_message_hash' => 'exceptionMessageHash',  // todo: generate hash or get from legacy?
//            ],
//        ],
//        'exception_traces' => [
//            [
//                'exception_trace' => 'exceptionTrace',
//                //'exception_trace_hash' => 'exceptionTraceHash',  // todo: generate hash or get from legacy?
//            ],
//        ],
    ];

    public function __construct(
        DatabaseManager $databaseManager,
        RequestRepository $requestRepository
    )
    {
        parent::__construct();

        $this->databaseManager = $databaseManager;
        $this->requestRepository = $requestRepository;
    }

    /**
     * return true
     */
    public function handle()
    {
        if($this->option('default')){
            $this->DrumeoLegacyTo4();
            return true;
        }

        $toRun = $this->promptForOption();

        $this->$toRun();
    }

    /**
     * @return bool|mixed
     */
    private function promptForOption()
    {
        $methodsAvailable = [
            'DrumeoLegacyTo4',
            'Drumeo3To4',
            'MusoraLegacyTo4',
            'Musora3To4',
            'Pianote3To4',
            'Guitareo3To4'
        ];

        $selection = false;
        while ($selection === false){

            foreach($methodsAvailable as $index => $methodAvailable){
                $this->info($index . '. ' . $methodAvailable);
            }

            $selection = $this->ask('Run which operation?');

            $notNumeric = !is_numeric($selection);
            $tooHigh = $selection > (count($methodsAvailable) - 1);

            if($notNumeric || $tooHigh){
                $this->info('Invalid. Try again' . PHP_EOL);
                $selection = false;
            }
        }

        return $methodsAvailable[$selection];
    }

    private function DrumeoLegacyTo4()
    {
        $this->info('------ STARTING DrumeoLegacyTo4 ------');

        $success = $this->databaseManager
            ->table('railtracker_requests')
            ->select(
                'railtracker_requests.*',
                'railtracker_requests.id as request_id'
            )

            // ------------ urls
            ->leftJoin('railtracker_urls as urls', 'railtracker_requests.url_id', '=', 'urls.id')
            ->leftJoin('railtracker_url_protocols as url_protocols', 'urls.protocol_id', '=', 'url_protocols.id')
            ->leftJoin('railtracker_url_domains as url_domains', 'urls.domain_id', '=', 'url_domains.id')
            ->leftJoin('railtracker_url_paths as url_paths', 'urls.path_id', '=', 'url_paths.id')
            ->leftJoin('railtracker_url_queries as url_queries', 'urls.query_id', '=', 'url_queries.id')
            ->addSelect(
                'url_protocols.protocol as url_protocol',
                'url_domains.name as url_name',
                'url_paths.path as url_path',
                'url_queries.string as url_query_string'
            )
            // ------------ referer urls
            ->leftJoin('railtracker_urls as referer_urls', 'railtracker_requests.referer_url_id', '=', 'referer_urls.id')
            ->leftJoin('railtracker_url_protocols as url_protocols_referers', 'referer_urls.protocol_id', '=', 'url_protocols_referers.id')
            ->leftJoin('railtracker_url_domains as url_domains_referers', 'referer_urls.domain_id', '=', 'url_domains_referers.id')
            ->leftJoin('railtracker_url_paths as url_paths_referers', 'referer_urls.path_id', '=', 'url_paths_referers.id')
            ->leftJoin('railtracker_url_queries as url_queries_referers', 'referer_urls.query_id', '=', 'url_queries_referers.id')
            ->addSelect(
                'url_protocols_referers.protocol as url_referer_protocol',
                'url_domains_referers.name as url_referer_name',
                'url_paths_referers.path as url_referer_path',
                'url_queries_referers.string as url_referer_query_string'
            )
            // ------------ routes, request_devices, request_agents, request_methods, request_languages, geoip
            ->leftJoin('railtracker_routes','railtracker_requests.route_id','=','railtracker_routes.id')
            ->leftJoin('railtracker_request_devices','railtracker_requests.device_id','=','railtracker_request_devices.id')
            ->leftJoin('railtracker_request_agents','railtracker_requests.agent_id','=','railtracker_request_agents.id')
            ->leftJoin('railtracker_request_methods','railtracker_requests.method_id','=','railtracker_request_methods.id')
            ->leftJoin('railtracker_request_languages','railtracker_requests.language_id','=','railtracker_request_languages.id')
            ->leftJoin('railtracker_geoip','railtracker_requests.geoip_id','=','railtracker_geoip.id')
            ->addSelect(
                'railtracker_routes.name as route_name',
                'railtracker_routes.action as route_action',
                'railtracker_routes.hash as route_hash', // is this needed?

                'railtracker_request_devices.kind as device_kind',
                'railtracker_request_devices.model as device_model',
                'railtracker_request_devices.platform as device_platform',
                'railtracker_request_devices.platform_version as device_platform_version',
                'railtracker_request_devices.is_mobile as device_is_mobile',
                'railtracker_request_devices.hash as device_hash', // is this needed?

                'railtracker_request_agents.name as agent_name',
                'railtracker_request_agents.browser as agent_browser',
                'railtracker_request_agents.browser_version as agent_browser_version',

                'railtracker_request_methods.method as method_method',

                'railtracker_request_languages.preference as language_preference',
                'railtracker_request_languages.language_range as language_language_range',

                'railtracker_geoip.latitude as geoip_latitude',
                'railtracker_geoip.longitude as geoip_longitude',
                'railtracker_geoip.country_code as geoip_country_code',
                'railtracker_geoip.country_name as geoip_country_name',
                'railtracker_geoip.region as geoip_region',
                'railtracker_geoip.city as geoip_city',
                'railtracker_geoip.postal_code as geoip_postal_code',
                'railtracker_geoip.ip_address as geoip_ip_address',
                'railtracker_geoip.timezone as geoip_timezone',
                'railtracker_geoip.currency as geoip_currency',
                'railtracker_geoip.hash as geoip_hash' // is this needed?
            )

            /*
             * Response and exception information is handled separately because the JOINS are too costly to include
             * here.
             *
             * Jonathan.M, Jan 2020
             */

            ->orderBy('id')
            ->limit($this->limit)
            ->chunk($this->chunkSize, function($rows){
                $this->migrateTheseRequests($rows);
            });

        $this->info('Success: ' . var_export($success, true));
    }

    private function fillHashes(Collection &$legacyData)
    {
//        $legacyDataWithHashes = [];
//
//        $setHashUnlessNull = function($value){
//            return !empty($value) ? md5($value) : null;
//        };

        foreach($legacyData as &$legacyDatum){
            /* * * * * * * * * * * * * * * * * * * * * * * *
                'url_query_hash' => 'urlQueryHash',
                'url_query_hash' => 'refererUrlQueryHash',
                'route_action_hash' => 'routeActionHash',
                'agent_string_hash' => 'agentStringHash',
             * * * * * * * * * * * * * * * * * * * * * * * */

            /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
                Given all this, are these actually needed in JOIN?
                    'railtracker_routes.hash as route_hash'
                    'railtracker_request_devices.hash as device_hash'
                    'railtracker_geoip.hash as geoip_hash'
             * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

            $legacyDatum->url_query_string_hash = null;
            $legacyDatum->url_referer_query_string_hash = null;
            $legacyDatum->route_action_hash = null;
            $legacyDatum->agent_name_hash = null;

            //dd($legacyDatum);

            if(isset($legacyDatum->url_query_string)){
                $legacyDatum->url_query_string_hash = md5($legacyDatum->url_query_string);
            }
            if(isset($legacyDatum->url_referer_query_string)){
                $legacyDatum->url_referer_query_string_hash = md5($legacyDatum->url_referer_query_string);
            }
            if(isset($legacyDatum->route_action)){
                $legacyDatum->route_action_hash = md5($legacyDatum->route_action);
            }
            if(isset($legacyDatum->agent_name)){
                $legacyDatum->agent_name_hash = md5($legacyDatum->agent_name);
            }
        }

        //dd($legacyData);
    }

    private function migrateTheseRequests(Collection $legacyData)
    {
        $this->fillHashes($legacyData);

        $dbConnectionName = config('railtracker.database_connection_name');

        $builder = new BulkInsertOrUpdateBuilder(
            $this->databaseManager->connection($dbConnectionName),
            new BulkInsertOrUpdateMySqlGrammar()
        );

        // first, linked data

        foreach(self::$map as $table => $fieldsForEachRow){

            $rowsToCreate = [];

            // 1.1 - prep linked data
            foreach($fieldsForEachRow as $columnPropertySets){
                foreach($legacyData as $legacyDatum){
                    $row = [];
                    foreach($columnPropertySets as $column => $property){
                        if(!isset($legacyDatum->$property)) continue;
                        $row[$column] = $legacyDatum->$property;
                    }
                    if(empty($row)) continue;
                    $rowsToCreate[] = $row;
                }
            }

            // 1.2 - store linked data (Note: copied from RequestRepository, but sqlite part omitted)
            if(empty($rowsToCreate)) continue;
            try{
                $builder->from(config('railtracker.table_prefix') . $table)->insertOrUpdate($rowsToCreate);
            }catch(\Exception $e){
                error_log($e);
                $this->info('Error while writing to association tables ("' . $e->getMessage() . '")');
            }
        }

        // second, store requests table

        $bulkInsertData = [];

        // -------------------------- replace this -----------------------------

//        /**
//         * @var $requestVOs RequestVO[]
//         */
//        foreach ($requestVOs as $requestVO) {
//            $bulkInsertData[] = $requestVO->returnArrayForDatabaseInteraction();
//        }

        // ---------------------------- with this ------------------------------

        foreach($legacyData as $legacyDatum){
            $bulkInsertData[] = [
                'uuid' =>                   $legacyDatum->uuid ?? null,                         // [x]
                'cookie_id' =>              $legacyDatum->cookie_id ?? null,                    // [x]
                'user_id' =>                $legacyDatum->user_id ?? null,                      // [x]
                'url_protocol' =>           $legacyDatum->url_protocol ?? null,                 // [x]
                'url_domain' =>             $legacyDatum->url_name ?? null,                     // [x]
                'url_path' =>               $legacyDatum->url_path ?? null,                     // [x]
                'method' =>                 $legacyDatum->method_method ?? null,                       // [ ]
                'route_name' =>             $legacyDatum->routeName ?? null,                    // [ ]
                'device_kind' =>            $legacyDatum->deviceKind ?? null,                   // [ ]
                'device_model' =>           $legacyDatum->deviceModel ?? null,                  // [ ]
                'device_platform' =>        $legacyDatum->devicePlatform ?? null,               // [ ]
                'device_version' =>         $legacyDatum->deviceVersion ?? null,                // [ ]
                'device_is_mobile' =>       $legacyDatum->deviceIsMobile ?? null,               // [ ]
                'agent_browser' =>          $legacyDatum->agentBrowser ?? null,                 // [ ]
                'agent_browser_version' =>  $legacyDatum->agentBrowserVersion ?? null,          // [ ]
                'referer_url_protocol' =>   $legacyDatum->refererUrlProtocol ?? null,           // [ ]
                'referer_url_domain' =>     $legacyDatum->refererUrlDomain ?? null,             // [ ]
                'referer_url_path' =>       $legacyDatum->refererUrlPath ?? null,               // [ ]
                'language_preference' =>    $legacyDatum->languagePreference ?? null,           // [ ]
                'language_range' =>         $legacyDatum->languageRange ?? null,                // [ ]
                'ip_address' =>             $legacyDatum->ipAddress ?? null,                    // [ ]
                'ip_latitude' =>            $legacyDatum->ipLatitude ?? null,                   // [ ]
                'ip_longitude' =>           $legacyDatum->ipLongitude ?? null,                  // [ ]
                'ip_country_code' =>        $legacyDatum->ipCountryCode ?? null,                // [ ]
                'ip_country_name' =>        $legacyDatum->ipCountryName ?? null,                // [ ]
                'ip_region' =>              $legacyDatum->ipRegion ?? null,                     // [ ]
                'ip_city' =>                $legacyDatum->ipCity ?? null,                       // [ ]
                'ip_postal_zip_code' =>     $legacyDatum->ipPostalZipCode ?? null,              // [ ]
                'ip_timezone' =>            $legacyDatum->ipTimezone ?? null,                   // [ ]
                'ip_currency' =>            $legacyDatum->ipCurrency ?? null,                   // [ ]
                'is_robot' =>               $legacyDatum->isRobot ?? null,                      // [ ]

                'exception_code' =>         $legacyDatum->exceptionCode ?? null,                // [ ]
                'exception_line' =>         $legacyDatum->exceptionLine ?? null,                // [ ]

                'requested_on' =>           $legacyDatum->requestedOn ?? null,                  // [ ]
                'response_status_code' =>   $legacyDatum->responseStatusCode ?? null,           // [ ]
                'response_duration_ms' =>   $legacyDatum->responseDurationMs ?? null,           // [ ]
                'responded_on' =>           $legacyDatum->respondedOn ?? null,                  // [ ]

                'url_query_hash' =>         $legacyDatum->url_query_string_hash ?? null,        // [x]
                'referer_url_query_hash' => $legacyDatum->url_referer_query_string_hash ?? null,// [x]
                'route_action_hash' =>      $legacyDatum->routeActionHash ?? null,              // [ ]
                'agent_string_hash' =>      $legacyDatum->agentStringHash ?? null,              // [ ]
                'exception_class_hash' =>   $legacyDatum->exceptionClassHash ?? null,           // [ ]
                'exception_file_hash' =>    $legacyDatum->exceptionFileHash ?? null,            // [ ]
                'exception_message_hash' => $legacyDatum->exceptionMessageHash ?? null,         // [ ]
                'exception_trace_hash' =>   $legacyDatum->exceptionTraceHash ?? null,           // [ ]
            ];
        }

        // ---------------------------------------------------------------------

        $table = config('railtracker.table_prefix') . 'requests';

        foreach(array_chunk($bulkInsertData, $this->chunkSize) as $chunkOfBulkInsertData){

            if (empty($chunkOfBulkInsertData)) continue;

            try{
                $builder->from($table)->insertOrUpdate($chunkOfBulkInsertData);
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

    private function Drumeo3To4()
    {
        $this->info('------ STARTING Drumeo3To4 ------');

        /*
        railtracker3_agent_browser_versions
        railtracker3_agent_browsers
        railtracker3_agent_strings
        railtracker3_device_kinds
        railtracker3_device_models
        railtracker3_device_platforms
        railtracker3_device_versions
        railtracker3_exception_classes
        railtracker3_exception_codes
        railtracker3_exception_files
        railtracker3_exception_lines
        railtracker3_exception_messages
        railtracker3_exception_traces
        railtracker3_ip_addresses
        railtracker3_ip_cities
        railtracker3_ip_country_codes
        railtracker3_ip_country_names
        railtracker3_ip_currencies
        railtracker3_ip_latitudes
        railtracker3_ip_longitudes
        railtracker3_ip_postal_zip_codes
        railtracker3_ip_regions
        railtracker3_ip_timezones
        railtracker3_language_preferences
        railtracker3_language_ranges
        railtracker3_methods
        railtracker3_requests
        railtracker3_response_durations
        railtracker3_response_status_codes
        railtracker3_route_actions
        railtracker3_route_names
        railtracker3_url_domains
        railtracker3_url_paths
        railtracker3_url_protocols
        railtracker3_url_queries
         */

        $this->info('TO DO');
    }

    private function MusoraLegacyTo4()
    {
        $this->info('------ STARTING MusoraLegacyTo4 ------');

        $this->info('TO DO');
    }

    private function Musora3To4()
    {
        $this->info('------ STARTING Musora3To4 ------');

        $this->info('TO DO');
    }

    private function Pianote3To4()
    {
        $this->info('------ STARTING Pianote3To4 ------');

        $this->info('TO DO');
    }

    private function Guitareo3To4()
    {
        $this->info('------ STARTING Guitareo3To4 ------');

        $this->info('TO DO');
    }

}
