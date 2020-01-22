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
    protected $signature = 'legacyMigrate {--run=}';

    /**
     * @var DatabaseManager
     */
    private $databaseManager;

    /**
     * @var RequestRepository
     */
    private $requestRepository;

    private $chunkSize = 100;

    //private $limitToOneChunk = true;

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
        $toRun = $this->promptForOption($this->option('run') ?? null);

        return $this->$toRun();
    }

    /**
     * @param null $supplied
     * @return bool|mixed
     */
    private function promptForOption($supplied = null)
    {
        $methodsAvailable = [
            'DrumeoLegacyTo4',
            'DrumeoLegacyTo4ResponsesAndExceptions',
            'Drumeo3To4',
            'MusoraLegacyTo4',
            'MusoraLegacyTo4ResponsesAndExceptions',
            'Musora3To4',
            'Pianote3To4',
            'Guitareo3To4'
        ];

        // get if supplied when command called.

        $notNumeric = !is_numeric($supplied);
        $tooHigh = $supplied > (count($methodsAvailable) - 1);

        $selection = $supplied;

        if($notNumeric || $tooHigh) {
            $this->info('Invalid. Select option from list or try again.' . PHP_EOL);
            $selection = false;
        }

        // else prompt for selection

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
            $legacyDatum->exception_exception_class_hash = null;
            $legacyDatum->exception_file_hash = null;
            $legacyDatum->exception_message_hash = null;
            $legacyDatum->exception_trace_hash = null;

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

            if(isset($legacyDatum->exception_exception_class)){
                $legacyDatum->exception_exception_class_hash = md5($legacyDatum->exception_exception_class);
            }
            if(isset($legacyDatum->exception_file)){
                $legacyDatum->exception_file_hash = md5($legacyDatum->exception_file);
            }
            if(isset($legacyDatum->exception_message)){
                $legacyDatum->exception_message_hash = md5($legacyDatum->exception_message);
            }
            if(isset($legacyDatum->exception_trace)){
                $legacyDatum->exception_trace_hash = md5($legacyDatum->exception_trace);
            }
        }

        //dd($legacyData);
    }

    private function migrateTheseRequests(Collection $legacyData)
    {
        $this->info('Processing ' . $legacyData->count() . ' legacy requests.');

        $this->fillHashes($legacyData);

        $dbConnectionName = config('railtracker.database_connection_name');

        $builder = new BulkInsertOrUpdateBuilder(
            $this->databaseManager->connection($dbConnectionName),
            new BulkInsertOrUpdateMySqlGrammar()
        );

        // first, linked data

        $specialCases = ['url_protocol','url_domain','url_path',];

        $map = [
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
                ['route_name' => 'route_name'],
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

            // more complex to join

            'response_status_codes' => [
                ['response_status_code' => 'response_status_code_code'],
            ],
            'response_durations' => [
                ['response_duration_ms' => 'response_duration_ms'],
            ],
            'exception_codes' => [
                ['exception_code' => 'exception_code'],
            ],
            'exception_lines' => [
                ['exception_line' => 'exception_line'],
            ],

            // long strings requiring hashes

            'url_queries' => [
                [
                    'url_query' => 'url_query_string',
                    'url_query_hash' => 'url_query_string_hash'
                ],
                [
                    'url_query' => 'url_referer_query_string',
                    'url_query_hash' => 'url_referer_query_string_hash'
                ]
            ],
            'route_actions' => [[
                'route_action' => 'route_action',
                'route_action_hash' => 'route_action_hash'
            ]],
            'agent_strings' => [[
                'agent_string' => 'agent_name',
                'agent_string_hash' => 'agent_name_hash'
            ]],
            'exception_classes' => [[
                'exception_class' => 'exception_exception_class',
                'exception_class_hash' => 'exception_exception_class_hash'
            ]],
            'exception_files' => [[
                'exception_file' => 'exception_file',
                'exception_file_hash' => 'exception_file_hash'
            ]],
            'exception_messages' => [[
                'exception_message' => 'exception_message',
                'exception_message_hash' => 'exception_message_hash'
            ]],
            'exception_traces' => [[
                'exception_trace' => 'exception_trace',
                'exception_trace_hash' => 'exception_trace_hash'
            ]],
        ];

        $notProvided = '[LEGACY]';

        foreach($map as $table => $fieldsForEachRow){

            $rowsToCreate = [];

            // 1.1 - prep linked data
            foreach($fieldsForEachRow as $columnPropertySets){
                foreach($legacyData as $legacyDatum){
                    $row = [];

                    foreach($columnPropertySets as $column => $property){

                        if(isset($legacyDatum->$property)){
                            $row[$column] = $legacyDatum->$property;
                        }

                        if(is_null($legacyDatum->$property) && in_array($column, $specialCases)){
                            $row[$column] = $notProvided;
                        }
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

        foreach($legacyData as $legacyDatum){

            $bulkInsertData[] = [
                'uuid' =>                   $legacyDatum->uuid ?? $notProvided, // NOT nullable
                'cookie_id' =>              $legacyDatum->cookie_id ?? null,
                'user_id' =>                $legacyDatum->user_id ?? null,
                'url_protocol' =>           $legacyDatum->url_protocol ?? $notProvided, // NOT nullable
                'url_domain' =>             $legacyDatum->url_name ?? $notProvided, // NOT nullable
                'url_path' =>               $legacyDatum->url_path ?? $notProvided, // NOT nullable
                'method' =>                 $legacyDatum->method_method ?? null,
                'route_name' =>             $legacyDatum->route_name ?? null,
                'device_kind' =>            $legacyDatum->device_kind ?? null,
                'device_model' =>           $legacyDatum->device_model ?? null,
                'device_platform' =>        $legacyDatum->device_platform ?? null,
                'device_version' =>         $legacyDatum->device_platform_version ?? null,
                'device_is_mobile' =>       $legacyDatum->device_is_mobile ?? $notProvided, // NOT nullable
                'agent_browser' =>          $legacyDatum->agent_browser ?? null,
                'agent_browser_version' =>  $legacyDatum->agent_browser_version ?? null,
                'referer_url_protocol' =>   $legacyDatum->url_referer_protocol ?? null,
                'referer_url_domain' =>     $legacyDatum->url_referer_name ?? null,
                'referer_url_path' =>       $legacyDatum->url_referer_path ?? null,
                'language_preference' =>    $legacyDatum->language_preference ?? null,
                'language_range' =>         $legacyDatum->language_language_range ?? null,
                'ip_address' =>             $legacyDatum->geoip_ip_address ?? null,

                /*
                 * I don't think we'll have any of the IP data—and that's fine because we're going to be running the
                 * "fill-missing-ip-data" command anyways.
                 */
                'ip_latitude' =>            $legacyDatum->geoip_latitude ?? null, // see note above
                'ip_longitude' =>           $legacyDatum->geoip_longitude ?? null, // see note above
                'ip_country_code' =>        $legacyDatum->geoip_country_code ?? null, // see note above
                'ip_country_name' =>        $legacyDatum->geoip_country_name ?? null, // see note above
                'ip_region' =>              $legacyDatum->geoip_region ?? null, // see note above
                'ip_city' =>                $legacyDatum->geoip_city ?? null, // see note above
                'ip_postal_zip_code' =>     $legacyDatum->geoip_postal_code ?? null, // see note above
                'ip_timezone' =>            $legacyDatum->ipTimezone ?? null, // see note above
                'ip_currency' =>            $legacyDatum->ipCurrency ?? null, // see note above

                'is_robot' =>               $legacyDatum->isRobot ?? 2, // NOT nullable

                'exception_code' =>         $legacyDatum->exception_code ?? null, // addressed elsewhere
                'exception_line' =>         $legacyDatum->exception_line ?? null, // addressed elsewhere

                'requested_on' =>           $legacyDatum->requested_on ?? $notProvided, // NOT nullable

                'response_status_code' =>   $legacyDatum->response_status_code_code ?? null, // addressed elsewhere
                'response_duration_ms' =>   $legacyDatum->response_duration_ms ?? null, // addressed elsewhere
                'responded_on' =>           $legacyDatum->respondedOn ?? null, // addressed elsewhere

                'url_query_hash' =>         $legacyDatum->url_query_string_hash ?? null,
                'referer_url_query_hash' => $legacyDatum->url_referer_query_string_hash ?? null,
                'route_action_hash' =>      $legacyDatum->route_action_hash ?? null,
                'agent_string_hash' =>      $legacyDatum->agent_name_hash ?? null,
                'exception_class_hash' =>   $legacyDatum->exception_exception_class_hash ?? null,
                'exception_file_hash' =>    $legacyDatum->exception_file_hash ?? null,
                'exception_message_hash' => $legacyDatum->exception_message_hash ?? null,
                'exception_trace_hash' =>   $legacyDatum->exception_trace_hash ?? null,
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

        if($this->limitToOneChunk ?? false) return false;

        return $presumablyCreatedRows->count();
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
                'urls.id as url_id_from_join',
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
                'railtracker_routes.action as route_action', // this will be the route_action_hash, but we still need
                // the scalar value to store in the association table.
                'railtracker_routes.hash as route_hash', // is this needed? I'm still not sure, but I know that this
                // will be the route_action_hash. It's probably note needed.

                'railtracker_request_devices.kind as device_kind',
                'railtracker_request_devices.model as device_model',
                'railtracker_request_devices.platform as device_platform',
                'railtracker_request_devices.platform_version as device_platform_version',
                'railtracker_request_devices.is_mobile as device_is_mobile',
                'railtracker_request_devices.hash as device_hash', // is this needed? Probably same as route_hash
                // above. I don't know if it's needed, but probably not.

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

            // responses

            ->leftJoin('railtracker_responses as responses', 'railtracker_requests.id', '=', 'responses.request_id')
            ->addSelect(
                'responses.response_duration_ms',
                'responses.responded_on'
            )
            ->leftJoin('railtracker_response_status_codes as response_status_codes', 'responses.status_code_id', '=', 'response_status_codes.id')
            ->addSelect(
                'response_status_codes.code AS response_status_code_code',
                'response_status_codes.hash AS response_status_code_hash'
            )

            // request-exceptions

            ->leftJoin('railtracker_request_exceptions as request_exceptions', 'railtracker_requests.id', '=', 'request_exceptions.request_id')
            ->addSelect(
                'request_exceptions.created_at_timestamp_ms AS exception_timestamp'
            )

            // exceptions

            ->leftJoin('railtracker_exceptions as exceptions', 'request_exceptions.exception_id', '=', 'exceptions.id')
            ->addSelect(
                'exceptions.id AS exception_id',
                'exceptions.code AS exception_code',
                'exceptions.line AS exception_line',
                'exceptions.exception_class AS exception_exception_class',
                'exceptions.file AS exception_file',
                'exceptions.message AS exception_message',
                'exceptions.trace AS exception_trace',
                'exceptions.hash AS exception_hash'
            )

            ->orderBy('id')
            ->chunk($this->chunkSize, function($rows){
                return $this->migrateTheseRequests($rows);
            });

        $this->info('Success: ' . var_export($success, true));
    }

    private function DrumeoLegacyTo4ResponsesAndExceptions()
    {
        $this->info('------ STARTING DrumeoLegacyTo4ResponsesAndExceptions ------');

        $success = $this->databaseManager
            ->table('railtracker_requests')
            ->select(
                'railtracker_requests.*',
                'railtracker_requests.id as request_id'
            )

            // responses

            ->leftJoin('railtracker_responses as responses', 'railtracker_requests.id', '=', 'responses.request_id')
            ->addSelect(
                'responses.response_duration_ms',
                'responses.responded_on'
            )
            ->leftJoin('railtracker_response_status_codes as response_status_codes', 'responses.status_code_id', '=', 'response_status_codes.id')
            ->addSelect(
                'response_status_codes.code AS response_status_code_code',
                'response_status_codes.hash AS response_status_code_hash'
            )

            // request-exceptions

            ->leftJoin('railtracker_request_exceptions as request_exceptions', 'railtracker_requests.id', '=', 'request_exceptions.request_id')
            ->addSelect(
                'request_exceptions.created_at_timestamp_ms AS exception_timestamp'
            )

            // exceptions

            ->leftJoin('railtracker_exceptions as exceptions', 'request_exceptions.exception_id', '=', 'exceptions.id')
            ->addSelect(
                'exceptions.id AS exception_id',
                'exceptions.code AS exception_code',
                'exceptions.line AS exception_line',
                'exceptions.exception_class AS exception_exception_class',
                'exceptions.file AS exception_file',
                'exceptions.message AS exception_message',
                'exceptions.trace AS exception_trace',
                'exceptions.hash AS exception_hash'
            )


            ->orderBy('id')
            ->chunk($this->chunkSize, function($rows){
                return $this->migrateTheseRequests($rows);
            });

        $this->info('Success: ' . var_export($success, true));
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

    private function MusoraLegacyTo4ResponsesAndExceptions()
    {
        $this->info('------ STARTING MusoraLegacyTo4ResponsesAndExceptions ------');

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
