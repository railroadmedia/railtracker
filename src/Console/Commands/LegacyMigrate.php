<?php

namespace Railroad\Railtracker\Console\Commands;

use Exception;
use Illuminate\Cookie\CookieJar;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Railroad\Railtracker\Events\RequestTracked;
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

    private static $map = [
        'url_protocols' => [ // table
            ['url_protocol' => 'url_protocol'], // row-name => property-name-in-processing-below
            ['url_protocol' => 'referer_url_protocol'],
        ],
        'url_domains' => [
            ['url_domain' => 'url_name'],
            ['url_domain' => 'referer_url_name'],
        ],
        'url_paths' => [
            ['url_path' => 'urlPath'],
            ['url_path' => 'refererUrlPath'],
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
                //'url_query_hash' => 'urlQueryHash', // todo: generate hash or get from legacy?
            ],
            [ // another row
                'url_query' => 'url_query_string',
                //'url_query_hash' => 'refererUrlQueryHash',  // todo: generate hash or get from legacy?
            ],
        ],

        'route_actions' => [
            [
                'route_action' => 'route_action',
                //'route_action_hash' => 'routeActionHash', // todo: generate hash or get from legacy?
            ],
        ],
        'agent_strings' => [
            [
                'agent_string' => 'agent_name',
                //'agent_string_hash' => 'agentStringHash',  // todo: generate hash or get from legacy?
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

        /*
        railtracker4_agent_browser_versions
        railtracker4_agent_browsers
        railtracker4_agent_strings
        railtracker4_device_kinds
        railtracker4_device_models
        railtracker4_device_platforms
        railtracker4_device_versions
        railtracker4_exception_classes
        railtracker4_exception_codes
        railtracker4_exception_files
        railtracker4_exception_lines
        railtracker4_exception_messages
        railtracker4_exception_traces
        railtracker4_ip_addresses
        railtracker4_ip_cities
        railtracker4_ip_country_codes
        railtracker4_ip_country_names
        railtracker4_ip_currencies
        railtracker4_ip_latitudes
        railtracker4_ip_longitudes
        railtracker4_ip_postal_zip_codes
        railtracker4_ip_regions
        railtracker4_ip_timezones
        railtracker4_language_preferences
        railtracker4_language_ranges
        railtracker4_methods
        railtracker4_requests
        railtracker4_response_durations
        railtracker4_response_status_codes
        railtracker4_route_actions
        railtracker4_route_names
        railtracker4_url_domains
        railtracker4_url_paths
        railtracker4_url_protocols
        railtracker4_url_queries
         */

        /*
        railtracker_exceptions
        railtracker_geoip
        railtracker_media_playback_sessions
        railtracker_media_playback_types
        railtracker_request_agents
        railtracker_request_devices
        railtracker_request_exceptions
        railtracker_request_languages
        railtracker_request_methods
        railtracker_requests
        railtracker_response_status_codes
        railtracker_responses
        railtracker_routes
        railtracker_url_domains
        railtracker_url_paths
        railtracker_url_protocols
        railtracker_url_queries
        railtracker_urls
         */

//        $chunkSize = 100;
        $chunkSize = 10;

        $results = $this->databaseManager
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
                'railtracker_routes.hash as route_hash',

                'railtracker_request_devices.kind as device_kind',
                'railtracker_request_devices.model as device_model',
                'railtracker_request_devices.platform as device_platform',
                'railtracker_request_devices.platform_version as device_platform_version',
                'railtracker_request_devices.is_mobile as device_is_mobile',
                'railtracker_request_devices.hash as device_hash',

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
                'railtracker_geoip.hash as geoip_hash'
            )

            /*
             * Response and exception information is handled separately because the JOINS are too costly to include
             * here.
             *
             * Jonathan.M, Jan 2020
             */

            ->orderBy('id')
            ->chunk($chunkSize, function($rows){
                $this->migrateTheseRequests($rows);
            });

        dump($results);
    }

    private function migrateTheseRequests(Collection $legacyData)
    {


//        foreach($legacyData as $row){
//
//        }

        //$this->requestRepository->storeRequests();

        // first, linked data

        // 1.1 - prep linked data

        foreach(self::$map as $table => $columnAndPropertyLabelHere){

//            dump($columnAndPropertyLabelHere);

            // note: will typically be just one, but sometimes is two, so just use foreach used anyways
            foreach($columnAndPropertyLabelHere as $columnPropertySets){

                foreach($legacyData as $legacyDatum){

                    foreach($columnPropertySets as $column => $property){
                        if(!isset($legacyDatum->$property)) continue;
                        $value =  $legacyDatum->$property;
                        $rowsToCreateByColumn[$table][$column][] = $value;
                    }
                }
            }
        }

        dd($rowsToCreateByColumn ?? []);

        die();

        // 1.2 - store linked data


        // second, store requests table






        die(); die(); die(); die(); die(); die(); die(); die();
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
