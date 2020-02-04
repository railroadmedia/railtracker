<?php

namespace Railroad\Railtracker\Console\Commands;

use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Railroad\Railtracker\QueryBuilders\BulkInsertOrUpdateBuilder;
use Railroad\Railtracker\QueryBuilders\BulkInsertOrUpdateMySqlGrammar;
use Railroad\Railtracker\Repositories\RequestRepository;

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

    private $chunkSize;
    private $deleteProcessed;
    private $stopOnFailure;
    private $limitChunkCount;
    private $unacceptableDeletionDuration;
    private $silenceBigUglyError;

    public function __construct(
        DatabaseManager $databaseManager,
        RequestRepository $requestRepository
    )
    {
        $this->chunkSize = config('railtracker.legacy_migrate_chunk_size') ?? 1000;
        $this->deleteProcessed = config('railtracker.legacy_migrate_delete_processed') ?? true;
        $this->stopOnFailure = config('railtracker.legacy_migrate_stop_on_failure') ?? true;
        $this->limitChunkCount = config('railtracker.legacy_migrate_limit_chunk_count') ?? false;
        $this->unacceptableDeletionDuration = config('railtracker.unacceptable_deletion_duration') ?? 250;
        $this->silenceBigUglyError = config('railtracker.silence_big_ugly_error') ?? false;

        parent::__construct();

        $this->databaseManager = $databaseManager;
        $this->requestRepository = $requestRepository;
    }

    /**
     * return true
     */
    public function handle()
    {
        $this->info('version-unique marker (**Should** be immediately-previous commit):');
        $this->info('https://github.com/railroadmedia/railtracker/commit/48d1f5bc8fe8ed09927370225bda0c1053430397');
        $this->info('--------------------------------------settings--------------------------------------');
        $this->info('    Chunk size: ' . $this->chunkSize);
        $this->info('    Processed rows ' . ($this->deleteProcessed ? 'WILL' : 'will NOT' ) . ' be deleted');
        $this->info('    "stopOnFailure" is set to: ' . ($this->stopOnFailure ? 'true' : 'false' ));
        $this->info('    "limitChunkCount" is set to: ' . ($this->limitChunkCount ? $this->limitChunkCount : 'false' ));
        $this->info('    "unacceptableDeletionDuration" is set to: ' . $this->unacceptableDeletionDuration);
        $this->info('    "silenceBigUglyError" is set to: ' . $this->silenceBigUglyError);
        $this->info('------------------------------------------------------------------------------------');
        $this->info('');

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
            'legacyToFour',
            'threeToFourAssociations',
            'threeToFourRequests',
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

    private function legacyToFour()
    {
        $this->info('#,duration(ms),avg(ms),vs avg as %,vs avg of 1st 5 as %,delete(ms)');

        $startTime = time();
        $chunkCounter = 0;
        $average = 0;
        $durations = [];
        $averageOfFirstFive = null;

        $this->databaseManager
            ->table('railtracker_requests')
            ->select('railtracker_requests.id')
            ->orderBy('id')
            ->chunkById(
                $this->chunkSize,
                function($ids) use (&$chunkCounter, &$average, &$durations, &$averageOfFirstFive) {

                    $chunkCounter++;

                    /** @var $ids Collection */

                    $idsToUse = [];

                    foreach($ids->toArray() as $idToUse){
                        $idsToUse[] = $idToUse->id;
                    }

                    $rows = $this->databaseManager
                        ->table('railtracker_requests')
                        ->select(
                            'railtracker_requests.*',
                            'railtracker_requests.id as id'
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
                        // ------------ routes, request_devices, request_agents, request_methods, request_languages
                        ->leftJoin('railtracker_routes','railtracker_requests.route_id','=','railtracker_routes.id')
                        ->leftJoin('railtracker_request_devices','railtracker_requests.device_id','=','railtracker_request_devices.id')
                        ->leftJoin('railtracker_request_agents','railtracker_requests.agent_id','=','railtracker_request_agents.id')
                        ->leftJoin('railtracker_request_methods','railtracker_requests.method_id','=','railtracker_request_methods.id')
                        ->leftJoin('railtracker_request_languages','railtracker_requests.language_id','=','railtracker_request_languages.id')
                        ->addSelect(
                            'railtracker_routes.name as route_name',
                            'railtracker_routes.action as route_action',

                            'railtracker_request_devices.kind as device_kind',
                            'railtracker_request_devices.model as device_model',
                            'railtracker_request_devices.platform as device_platform',
                            'railtracker_request_devices.platform_version as device_platform_version',
                            'railtracker_request_devices.is_mobile as device_is_mobile',

                            'railtracker_request_agents.name as agent_name',
                            'railtracker_request_agents.browser as agent_browser',
                            'railtracker_request_agents.browser_version as agent_browser_version',

                            'railtracker_request_methods.method as method_method',

                            'railtracker_request_languages.preference as language_preference',
                            'railtracker_request_languages.language_range as language_language_range'
                        )
                        // geo_ip
                        ->leftJoin('railtracker_geoip','railtracker_requests.geoip_id','=','railtracker_geoip.id')
                        ->addSelect(
                            'railtracker_geoip.latitude as geoip_latitude',
                            'railtracker_geoip.longitude as geoip_longitude',
                            'railtracker_geoip.country_code as geoip_country_code',
                            'railtracker_geoip.country_name as geoip_country_name',
                            'railtracker_geoip.region as geoip_region',
                            'railtracker_geoip.city as geoip_city',
                            'railtracker_geoip.postal_code as geoip_postal_code',
                            'railtracker_geoip.ip_address as geoip_ip_address',
                            'railtracker_geoip.timezone as geoip_timezone',
                            'railtracker_geoip.currency as geoip_currency'
                        )
                        // responses
                        ->leftJoin('railtracker_responses as responses', 'railtracker_requests.id', '=', 'responses.id')
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
                        ->leftJoin('railtracker_request_exceptions as request_exceptions', 'railtracker_requests.id', '=', 'request_exceptions.id')
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
                        ->whereIn('railtracker_requests.id', $idsToUse)
                        ->get();

                    $start = round(microtime(true) * 1000);

                    try{
                        $success = $this->migrateTheseRequests($rows);
                    }catch(\Exception $e){
                        $this->info($e->getMessage());
                        error_log($e);
                        return false; // do not process any more chunks
                    }

                    if($this->deleteProcessed){
                        $idsToDelete = [];
                        foreach($rows as $row){
                            $idsToDelete[] = $row->id;
                        }

                        $highest = max($idsToDelete);
                        $lowest = min($idsToDelete);

                        $deleteQuery = "DELETE FROM railtracker_requests WHERE id >= $lowest AND id <= $highest";

                        //$this->info('Running delete query "' . $deleteQuery . '"');

                        $deleteStartTime = round(microtime(true) * 1000);

                        $this->databaseManager->connection()->delete($deleteQuery);

                        $deleteEndTime = round(microtime(true) * 1000);

                        $deleteDuration = $deleteEndTime - $deleteStartTime;

                        if($deleteDuration > $this->unacceptableDeletionDuration){
                            $this->info('Notice: deletion took more than ' . $this->unacceptableDeletionDuration .
                                'ms (namely: ' . $deleteDuration . 'ms)');
                        }
                    }

                    $end = round(microtime(true) * 1000);
                    $duration = round($end - $start);
                    $durations[] = $duration;
                    $average = round(array_sum($durations) / $chunkCounter);
                    $percentDifferenceFromAverageOfFirstFive = 'n/a';
                    if($chunkCounter > 5){
                        $percentDifferenceFromAverageOfFirstFive = round($duration/$averageOfFirstFive,2) * 100;
                    }else{
                        $averageOfFirstFive = $average;
                    }
                    $percentDifferenceFromAverage = round($duration/$average, 2)*100;

                    $this->info(
                        $chunkCounter . ',' .
                        $duration . ',' .
                        $average . ',' .
                        $percentDifferenceFromAverage. ',' .
                        $percentDifferenceFromAverageOfFirstFive . ',' .
                        ($deleteDuration ?? 'n/a')
                    );

                    if($this->stopOnFailure){
                        if(!$success) return false; // do not process any more chunks on failure
                    }

                    // Limit how many chunks to process—handy for debugging|dev.
                    if($this->limitChunkCount !== false){
                        if($chunkCounter > $this->limitChunkCount) return false;
                    }

                    return true;
                }
            );

        // just a helpful message to the user
        $minutes = (int) floor((time() - $startTime)/60);
        $secondsRemaining = time() - $startTime - ($minutes * 60);
        $secondsRemaining = $secondsRemaining <= 9 ? '0' . $secondsRemaining : $secondsRemaining;
        $this->info('');
        $this->info('Finished. Total duration was: ' . $minutes . ':' . $secondsRemaining . ' (mmm:ss)');
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
                    if(in_array($row, $rowsToCreate, true)) continue;

                    $rowsToCreate[] = $row;
                }
            }

            // 1.2 - store linked data (Note: copied from RequestRepository, but sqlite part omitted)
            if(empty($rowsToCreate)) continue;

            $columns = [];
            $stringsForRows = [];

            foreach($rowsToCreate as $rowToPrep){
                foreach($rowToPrep as $columnName => $value){
                    if(!in_array($columnName, $columns)){
                        $columns[] = $columnName;
                    }
                }
            }

            foreach($rowsToCreate as $rowToPrep){
                $rowItemsForString = [];
                foreach($columns as $column){
                    // escape single quotation-marks because they're used by our query
                    if(!isset($rowToPrep[$column])){
                        error_log('Value for column (\'' . $column . '\') not defined. This should not be possible.');
                        continue;
                    }
                    $value = $rowToPrep[$column];
                    $value = str_replace('\'', '\\\'', $value); // todo: is this needed?
                    $value = '\'' . $value . '\'';
                    $rowItemsForString[] = $value;
                }
                $stringsForRows[] = '(' . implode(', ', $rowItemsForString) . ')';
            }
            $parametersString = implode(', ', $stringsForRows);
            $columnsString = implode(', ', $columns);

            $sql = "insert ignore into railtracker4_$table ($columnsString) values $parametersString";

            $this->databaseManager->connection()->insert($sql);


            // todo: hol up, why is this here?
            // todo: hol up, why is this here?
            // todo: hol up, why is this here?
            // todo: hol up, why is this here?
            // todo: hol up, why is this here?
            try{
                $builder->raw($sql);
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

        return $presumablyCreatedRows->count();
    }

    private function threeToFourRequests()
    {
        $this->info('starting threeToFourRequests');

        $entireStart = round(microtime(true) * 1000);

        $table = 'railtracker3_requests';

        $columnsToTransfer = [
            'id',
            'uuid',
            'cookie_id',
            'user_id',
            'url_protocol',
            'url_domain',
            'url_path',
            'referer_url_protocol',
            'referer_url_domain',
            'referer_url_path',
            'method',
            'route_name',
            'device_kind',
            'device_model',
            'device_platform',
            'device_version',
            'device_is_mobile',
            'agent_browser',
            'agent_browser_version',
            'language_preference',
            'language_range',
            'ip_address',
            'ip_latitude',
            'ip_longitude',
            'ip_country_code',
            'ip_country_name',
            'ip_region',
            'ip_city',
            'ip_postal_zip_code',
            'ip_timezone',
            'ip_currency',
            'is_robot',
            'response_status_code',
            'response_duration_ms',
            'exception_code',
            'exception_line',
            'requested_on',
            'responded_on',
            'url_query_hash',
            'referer_url_query_hash',
            'route_action_hash',
            'agent_string_hash',
            'exception_class_hash',
            'exception_file_hash',
            'exception_message_hash',
            'exception_trace_hash',
        ];
        $chunkCount = 0;

        $empty = false;

        $this->info('chunkCount,insertedOrUpdated,duration(ms),deletionSuccess,deleteDuration');

        $limit = $this->chunkSize;

        $setAside = [];
        $unableToGet = [];
        $onlySetAsideRemains = false;

        while(!$empty){

            $deletionOperationSuccess = '0';
            $deleteDuration = 'n/a';

            sleep(1);

            $startTime = round(microtime(true) * 1000);

            $chunkCount++;

            $query = $this->databaseManager->table($table)->select($columnsToTransfer);

            if(!$onlySetAsideRemains) $query = $query->whereNotIn('uuid', $setAside);

            $rows = $query->orderBy('id')->limit($limit)->get();

            // if result is less than chunkSize, you must change limit or you will have an infinite loop
            if(count($rows) < $this->chunkSize) $limit = count($rows);

            // After we try processing $onlySetAsideRemains without any success
            if($onlySetAsideRemains && count($rows) === 0){
                $unableToGet = array_merge($unableToGet, $setAside);
                $empty = true;
            }
            if($empty) continue;

            // after all the easy rows are done and only those set aside in $onlySetAsideRemains remain
            if(!$onlySetAsideRemains && count($rows) === 0) $onlySetAsideRemains = true;
            if(count($rows) === 0) continue;

            $insertedOrUpdated = $this->transferTheseRow($rows, $table, true);

            $endTime = round(microtime(true) * 1000);
            $duration = $endTime - $startTime;

            // figure out what rows actually inserted, so that we can delete only those, but also so those not inserted
            // can be set aside to address later and thus reduce the query time above in each iteration
            $uuidsSuccessfullyTransferred = [];
            foreach($insertedOrUpdated as $row){
                if(!isset($row->uuid)){
                    $this->info('UUID missing in delete eval in threeToFourRequests(). This should not be possible');
                    if($this->stopOnFailure) die();
                    continue;
                }
                $uuidsSuccessfullyTransferred[] = '\'' . $row->uuid . '\'';
            }

            // rows not migrated to set aside for later lest they slow query on each iteration
            foreach($rows as $row){
                if(!in_array(('\'' . $row->uuid . '\''), $uuidsSuccessfullyTransferred)){
                    if(!in_array($row->uuid, $setAside)){
                        $setAside[] = $row->uuid;
                    }else{
                        if($onlySetAsideRemains){ // unable to get this uuid even at the end

                            $this->info('unable to get this uuid even at the end: ' . $row->uuid);

                            if (($key = array_search($row->uuid, $setAside)) !== false) {
                                unset($setAside[$key]);
                            }
                            $unableToGet[] = $row->uuid;
                        }
                    }
                }
            }

            // only delete those rows that have been inserted
            if(!empty($uuidsSuccessfullyTransferred)){
                $deleteStartTime = round(microtime(true) * 1000);
                $parameters = implode(',', $uuidsSuccessfullyTransferred);
                $sql = "delete from railtracker3_requests where uuid in ($parameters)";
                $deletionOperationSuccess = $this->databaseManager->connection()->delete($sql);
                if(!$deletionOperationSuccess){
                    $this->info('Failed to delete railtracker3_requests rows: ' . $parameters);
                    if($this->stopOnFailure) die();
                }

                $deleteDuration = round(microtime(true) * 1000) - $deleteStartTime;
            }

            // ------------------------------------

            $deletionSuccess = $deletionOperationSuccess ? '1' : '0';
            $this->info($chunkCount . ',' . count($insertedOrUpdated) . ',' . $duration . ',' . $deletionSuccess . ',' . $deleteDuration);
        }

        $this->info('');
        $this->info('done!');
        $entireDurationSeconds = (round(microtime(true) * 1000) - $entireStart) / 1000;
        $entireDurationMinutesNoRemainder = floor($entireDurationSeconds/60);
        $entireDurationRemainderSeconds = round($entireDurationSeconds - ($entireDurationMinutesNoRemainder * 60));
        $this->info('Duration: ' . $entireDurationMinutesNoRemainder . ' minutes and ' . $entireDurationRemainderSeconds . ' seconds');

        if(!empty($unableToGet)){
            $this->info('unable to transfer the rows with these uuids: ' . var_export($unableToGet, true));
        }else{
            $this->info('Successfully transferred all rows!');
        }
    }

    private function threeToFourAssociations()
    {
        $tablesToTransfer = [
            'railtracker3_agent_browser_versions' => ['agent_browser_version'],
            'railtracker3_agent_browsers' => ['agent_browser'],
            'railtracker3_agent_strings' => ['agent_string_hash','agent_string'],
            'railtracker3_device_kinds' => ['device_kind'],
            'railtracker3_device_models' => ['device_model'],
            'railtracker3_device_platforms' => ['device_platform'],
            'railtracker3_device_versions' => ['device_version'],
            'railtracker3_exception_classes' => ['exception_class_hash','exception_class'],
            'railtracker3_exception_codes' => ['exception_code'],
            'railtracker3_exception_files' => ['exception_file_hash','exception_file'],
            'railtracker3_exception_lines' => ['exception_line'],
            'railtracker3_exception_messages' => ['exception_message_hash','exception_message'],
            'railtracker3_exception_traces' => ['exception_trace_hash','exception_trace'],
            'railtracker3_ip_addresses' => ['ip_address'],
            'railtracker3_ip_cities' => ['ip_city'],
            'railtracker3_ip_country_codes' => ['ip_country_code'],
            'railtracker3_ip_country_names' => ['ip_country_name'],
            'railtracker3_ip_currencies' => ['ip_currency'],
            'railtracker3_ip_latitudes' => ['ip_latitude'],
            'railtracker3_ip_longitudes' => ['ip_longitude'],
            'railtracker3_ip_postal_zip_codes' => ['ip_postal_zip_code'],
            'railtracker3_ip_regions' => ['ip_region'],
            'railtracker3_ip_timezones' => ['ip_timezone'],
            'railtracker3_language_preferences' => ['language_preference'],
            'railtracker3_language_ranges' => ['language_range'],
            'railtracker3_methods' => ['method'],
            'railtracker3_response_durations' => ['response_duration_ms'],
            'railtracker3_response_status_codes' => ['response_status_code'],
            'railtracker3_route_actions' => ['route_action_hash','route_action'],
            'railtracker3_route_names' => ['route_name'],
            'railtracker3_url_domains' => ['url_domain'],
            'railtracker3_url_paths' => ['url_path'],
            'railtracker3_url_protocols' => ['url_protocol'],
            'railtracker3_url_queries' => ['url_query_hash','url_query'],
        ];

        $this->info('table,chunkCount,successful,duration(ms),deleted,del-dur(ms)');

        foreach($tablesToTransfer as $table => $columnsToTransfer){
            sleep(1);

            $chunkCount = 0;

            $orderByColumn = reset($columnsToTransfer);

            $empty = false;

            $this->info($table . ',chunkCount,successful,duration(ms),deleted,del-dur(ms)');

            while(!$empty){

                $deletionOperationSuccess = false;
                $deleteDuration = 'n/a';

                sleep(1);

                $startTime = round(microtime(true) * 1000);

                $chunkCount++;

                $skip = ($chunkCount - 1) * $this->chunkSize;

                $rows = $this->databaseManager
                    ->table($table)
                    ->select($columnsToTransfer)
                    ->orderBy($orderByColumn)
                    ->limit($this->chunkSize)
                    ->skip($skip)
                    ->get();

                $empty = count($rows) === 0;

                if($empty) continue;

                $success = $this->transferTheseRow($rows, $table);

                $endTime = round(microtime(true) * 1000);

                $duration = $endTime - $startTime;

                // ------------------------------------------------------------------------

//                $deleteStartTime = round(microtime(true) * 1000);
//
//                $parameters = [];
//
//                foreach($rows as $row){
//                    $value = $row->$orderByColumn;
//                    if(gettype($value) === 'string'){
//                        // escape single quotation-marks because they're used by our query
//                        $value = str_replace('\'', '\\\'', $value);
//                        $value = '\'' . $value . '\'';
//                    }
//                    $parameters[] = $value;
//                }
//
//                $parametersImploded = implode(',', $parameters);
//                $parametersString = '(' . $parametersImploded . ')';
//
//                $sql = "delete from $table where $orderByColumn in $parametersString";
//
//                $deletionOperationSuccess = $this->databaseManager->connection()->delete($sql);
//
//                if(!$deletionOperationSuccess){
//                    $this->info('Failed to delete railtracker3_requests rows: ' . $parametersString);
//                    if($this->stopOnFailure){
//                        die();
//                    }
//                }
//
//                $deleteEndTime = round(microtime(true) * 1000);
//
//                $deleteDuration = $deleteEndTime - $deleteStartTime;

                // ------------------------------------------------------------------------

                $successful = $success ? '1' : '0';
                $deletionSuccess = $deletionOperationSuccess ? '1' : '0';
                $this->info(',' . $chunkCount . ',' . $successful . ',' . $duration . ',' . $deletionSuccess .
                    ',' . $deleteDuration);
            }
        }
        $this->info('done!');
    }

    private function transferTheseRow($rows, $table, $returnGet = false)
    {
        foreach($rows as $row){
            $data[] = json_decode(json_encode($row), true);
        }

        $tableToUpdate = str_replace_first('3', '4', $table);

        try{
            $columns = [];
            $stringsForRows = [];

            foreach($rows as $rowToPrep){
                foreach($rowToPrep as $columnName => $value){
                    if(!in_array($columnName, $columns)){
                        $columns[] = $columnName;
                    }
                }
            }

            $uuids = [];
            foreach($rows as $rowToPrep){
                $rowItemsForString = [];
                foreach($columns as $column){
                    $value = 'NULL';
                    if(isset($rowToPrep->$column)){
                        $value = $rowToPrep->$column;
                        // escape single quotation-marks because they're used by our query
                        $value = str_replace('\'', '\\\'', $value);
                        $value = '\'' . $value . '\'';

                        if($returnGet && ($column === 'uuid')){
                            $uuids[] = $value;
                        }
                    }
                    $rowItemsForString[] = $value;
                }
                $stringsForRows[] = '(' . implode(', ', $rowItemsForString) . ')';
            }
            $parametersString = implode(', ', $stringsForRows);
            $columnsString = implode(', ', $columns);

            $insertQuery = "insert ignore into $tableToUpdate ($columnsString) values $parametersString";

            $insertResult = $this->databaseManager->connection()->insert($insertQuery);

            if($returnGet){
                if(empty($uuids)) return [];
                $uuidsAsString = '(' . implode(',', $uuids) . ')';
                $selectQuery = "SELECT * FROM $tableToUpdate WHERE uuid in $uuidsAsString";
                $rows = $this->databaseManager->connection()->select($selectQuery);
                return $rows;
            }

            return $insertResult;

        }catch(\Exception $e){
            error_log($e);
            if($this->silenceBigUglyError){
                dump('Error while writing to requests table ("' . $e->getMessage() . '")');
            }
            if($this->stopOnFailure){
                if($returnGet) return [];
                return false;
            }
        }
    }
}
