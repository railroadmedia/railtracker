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

    public function __construct(
        DatabaseManager $databaseManager
    )
    {
        parent::__construct();

        $this->databaseManager = $databaseManager;
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
        $chunkSize = 2;

        // join these...?
        /*

        railtracker_exceptions
        railtracker_geoip

uuid
user_id
cookie_id
url_id
route_id
device_id
agent_id
method_id
referer_url_id
language_id
geoip_id
client_ip
is_robot
requested_on

         */

        $results = $this->databaseManager
            ->table('railtracker_requests')

            ->join('railtracker_urls', 'railtracker_requests.url_id', '=', 'railtracker_urls.id')

            ->join('railtracker_url_protocols', 'railtracker_urls.protocol_id', '=', 'railtracker_url_protocols.id')
            ->join('railtracker_url_domains', 'railtracker_urls.domain_id', '=', 'railtracker_url_domains.id')
            ->join('railtracker_url_paths', 'railtracker_urls.path_id', '=', 'railtracker_url_paths.id')
            ->join('railtracker_url_queries', 'railtracker_urls.query_id', '=', 'railtracker_url_queries.id')

            ->select('railtracker_requests.*',
//                'railtracker_urls.*',
                'railtracker_url_protocols.protocol',
                'railtracker_url_domains.name',
                'railtracker_url_paths.path',
                'railtracker_url_queries.string'
            )

            ->orderBy('railtracker_requests.id')
            ->limit(1)
            ->get();


        dd($results);

//            ->chunkById($chunkSize, function($rows){
//                $this->migrateTheseRequests($rows);
//                die();
//                die();
//                die();
//                die();
//                die();
//            });

        dump($results);
    }

    private function migrateTheseRequests($rows)
    {
        dump($rows);
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
