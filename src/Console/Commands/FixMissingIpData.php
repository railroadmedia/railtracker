<?php

namespace Railroad\Railtracker\Console\Commands;

use Carbon\Carbon;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Railroad\Railtracker\Services\IpDataApiSdkService;

class FixMissingIpData extends \Illuminate\Console\Command
{
    /**
     * @var string
     */
    protected $name = 'FixMissingIpData';

    /**
     * @var string
     */
    protected $description = 'Fix missing IP-Data.';

    protected $signature = 'fixMissingIpData {selection?}';

    private static $PAUSE_ON_LOCAL = false;

    private static $ACTIONS = [
        'tempTableAdd',
        'tempTableFill',
        'updateRequests',
    ];

    private static $DEV_MODE = true;

    private static $DEV_AID_1 = [
        [
            "ip_latitude" => null,
            "ip_longitude" => null,
            "ip_country_code" => null,
            "ip_country_name" => null,
            "ip_region" => null,
            "ip_city" => null,
            "ip_postal_zip_code" => null,
            "ip_timezone" => null,
            "ip_currency" => null,
            "private" => true,
            "failed" => true,
        ],[
            "ip_latitude" => null,
            "ip_longitude" => null,
            "ip_country_code" => null,
            "ip_country_name" => null,
            "ip_region" => null,
            "ip_city" => null,
            "ip_postal_zip_code" => null,
            "ip_timezone" => null,
            "ip_currency" => null,
            "private" => false,
            "failed" => true,
        ],[
            "ip_latitude" => null,
            "ip_longitude" => null,
            "ip_country_code" => null,
            "ip_country_name" => null,
            "ip_region" => null,
            "ip_city" => null,
            "ip_postal_zip_code" => null,
            "ip_timezone" => null,
            "ip_currency" => null,
            "private" => true,
            "failed" => true,
        ],[
            "ip_latitude" => null,
            "ip_longitude" => null,
            "ip_country_code" => null,
            "ip_country_name" => null,
            "ip_region" => null,
            "ip_city" => null,
            "ip_postal_zip_code" => null,
            "ip_timezone" => null,
            "ip_currency" => null,
            "private" => true,
            "failed" => true,
        ],[
            "ip_latitude" => null,
            "ip_longitude" => null,
            "ip_country_code" => null,
            "ip_country_name" => null,
            "ip_region" => null,
            "ip_city" => null,
            "ip_postal_zip_code" => null,
            "ip_timezone" => null,
            "ip_currency" => null,
            "private" => false,
            "failed" => true,
        ],[
            "ip_latitude" => null,
            "ip_longitude" => null,
            "ip_country_code" => null,
            "ip_country_name" => null,
            "ip_region" => null,
            "ip_city" => null,
            "ip_postal_zip_code" => null,
            "ip_timezone" => null,
            "ip_currency" => null,
            "private" => true,
            "failed" => true,
        ],[
            "ip_latitude" => null,
            "ip_longitude" => null,
            "ip_country_code" => null,
            "ip_country_name" => null,
            "ip_region" => null,
            "ip_city" => null,
            "ip_postal_zip_code" => null,
            "ip_timezone" => null,
            "ip_currency" => null,
            "private" => true,
            "failed" => true,
        ],[
            "ip_latitude" => null,
            "ip_longitude" => null,
            "ip_country_code" => null,
            "ip_country_name" => null,
            "ip_region" => null,
            "ip_city" => null,
            "ip_postal_zip_code" => null,
            "ip_timezone" => null,
            "ip_currency" => null,
            "private" => true,
            "failed" => true,
        ],[
            "ip_latitude" => null,
            "ip_longitude" => null,
            "ip_country_code" => null,
            "ip_country_name" => null,
            "ip_region" => null,
            "ip_city" => null,
            "ip_postal_zip_code" => null,
            "ip_timezone" => null,
            "ip_currency" => null,
            "private" => true,
            "failed" => true,
        ],[
            "ip_latitude" => null,
            "ip_longitude" => null,
            "ip_country_code" => null,
            "ip_country_name" => null,
            "ip_region" => null,
            "ip_city" => null,
            "ip_postal_zip_code" => null,
            "ip_timezone" => null,
            "ip_currency" => null,
            "private" => true,
            "failed" => true,
        ],[
            "ip_latitude" => null,
            "ip_longitude" => null,
            "ip_country_code" => null,
            "ip_country_name" => null,
            "ip_region" => null,
            "ip_city" => null,
            "ip_postal_zip_code" => null,
            "ip_timezone" => null,
            "ip_currency" => null,
            "private" => true,
            "failed" => true,
        ],[
            "ip_latitude" => null,
            "ip_longitude" => null,
            "ip_country_code" => null,
            "ip_country_name" => null,
            "ip_region" => null,
            "ip_city" => null,
            "ip_postal_zip_code" => null,
            "ip_timezone" => null,
            "ip_currency" => null,
            "private" => false,
            "failed" => true,
        ],[
            "ip_latitude" => null,
            "ip_longitude" => null,
            "ip_country_code" => null,
            "ip_country_name" => null,
            "ip_region" => null,
            "ip_city" => null,
            "ip_postal_zip_code" => null,
            "ip_timezone" => null,
            "ip_currency" => null,
            "private" => true,
            "failed" => true,
        ],[
            "ip_latitude" => null,
            "ip_longitude" => null,
            "ip_country_code" => null,
            "ip_country_name" => null,
            "ip_region" => null,
            "ip_city" => null,
            "ip_postal_zip_code" => null,
            "ip_timezone" => null,
            "ip_currency" => null,
            "private" => false,
            "failed" => true,
        ],[
            "ip_latitude" => null,
            "ip_longitude" => null,
            "ip_country_code" => null,
            "ip_country_name" => null,
            "ip_region" => null,
            "ip_city" => null,
            "ip_postal_zip_code" => null,
            "ip_timezone" => null,
            "ip_currency" => null,
            "private" => true,
            "failed" => true,
        ],[
            "ip_latitude" => null,
            "ip_longitude" => null,
            "ip_country_code" => null,
            "ip_country_name" => null,
            "ip_region" => null,
            "ip_city" => null,
            "ip_postal_zip_code" => null,
            "ip_timezone" => null,
            "ip_currency" => null,
            "private" => false,
            "failed" => true,
        ],[
            "ip_latitude" => null,
            "ip_longitude" => null,
            "ip_country_code" => null,
            "ip_country_name" => null,
            "ip_region" => null,
            "ip_city" => null,
            "ip_postal_zip_code" => null,
            "ip_timezone" => null,
            "ip_currency" => null,
            "private" => true,
            "failed" => true,
        ],[
            "ip_latitude" => 19.5576,
            "ip_longitude" => -99.2331,
            "ip_country_code" => "MX",
            "ip_country_name" => "Mexico",
            "ip_region" => "México",
            "ip_city" => "Ciudad Lopez Mateos",
            "ip_postal_zip_code" => "52953",
            "ip_timezone" => "America/Mexico_City",
            "ip_currency" => "MXN",
            "private" => false,
            "failed" => false,
        ],[
            "ip_latitude" => 40.4735,
            "ip_longitude" => -79.9558,
            "ip_country_code" => "US",
            "ip_country_name" => "United States",
            "ip_region" => "Pennsylvania",
            "ip_city" => "Pittsburgh",
            "ip_postal_zip_code" => "15201",
            "ip_timezone" => "America/New_York",
            "ip_currency" => "USD",
            "private" => false,
            "failed" => false,
        ],[
            "ip_latitude" => null,
            "ip_longitude" => null,
            "ip_country_code" => null,
            "ip_country_name" => null,
            "ip_region" => null,
            "ip_city" => null,
            "ip_postal_zip_code" => null,
            "ip_timezone" => null,
            "ip_currency" => null,
            "private" => false,
            "failed" => true,
        ],[
            "ip_latitude" => null,
            "ip_longitude" => null,
            "ip_country_code" => null,
            "ip_country_name" => null,
            "ip_region" => null,
            "ip_city" => null,
            "ip_postal_zip_code" => null,
            "ip_timezone" => null,
            "ip_currency" => null,
            "private" => false,
            "failed" => true,
        ],[
            "ip_latitude" => null,
            "ip_longitude" => null,
            "ip_country_code" => null,
            "ip_country_name" => null,
            "ip_region" => null,
            "ip_city" => null,
            "ip_postal_zip_code" => null,
            "ip_timezone" => null,
            "ip_currency" => null,
            "private" => true,
            "failed" => true,
        ],[
            "ip_latitude" => null,
            "ip_longitude" => null,
            "ip_country_code" => null,
            "ip_country_name" => null,
            "ip_region" => null,
            "ip_city" => null,
            "ip_postal_zip_code" => null,
            "ip_timezone" => null,
            "ip_currency" => null,
            "private" => false,
            "failed" => true,
        ],[
            "ip_latitude" => null,
            "ip_longitude" => null,
            "ip_country_code" => null,
            "ip_country_name" => null,
            "ip_region" => null,
            "ip_city" => null,
            "ip_postal_zip_code" => null,
            "ip_timezone" => null,
            "ip_currency" => null,
            "private" => true,
            "failed" => true,
        ],[
            "ip_latitude" => null,
            "ip_longitude" => null,
            "ip_country_code" => null,
            "ip_country_name" => null,
            "ip_region" => null,
            "ip_city" => null,
            "ip_postal_zip_code" => null,
            "ip_timezone" => null,
            "ip_currency" => null,
            "private" => true,
            "failed" => true,
        ],[
            "ip_latitude" => 43.6644,
            "ip_longitude" => -79.4195,
            "ip_country_code" => "CA",
            "ip_country_name" => "Canada",
            "ip_region" => "Ontario",
            "ip_city" => "Toronto",
            "ip_postal_zip_code" => "M6G",
            "ip_timezone" => "America/Toronto",
            "ip_currency" => "CAD",
            "private" => false,
            "failed" => false,
        ],[
            "ip_latitude" => 48.1968,
            "ip_longitude" => 16.3191,
            "ip_country_code" => "AT",
            "ip_country_name" => "Austria",
            "ip_region" => "Vienna",
            "ip_city" => "Vienna",
            "ip_postal_zip_code" => "1150",
            "ip_timezone" => "Europe/Vienna",
            "ip_currency" => "EUR",
            "private" => false,
            "failed" => false,
        ],[
            "ip_latitude" => 35.7965,
            "ip_longitude" => -78.7981,
            "ip_country_code" => "US",
            "ip_country_name" => "United States",
            "ip_region" => "North Carolina",
            "ip_city" => "Cary",
            "ip_postal_zip_code" => "27513",
            "ip_timezone" => "America/New_York",
            "ip_currency" => "USD",
            "private" => false,
            "failed" => false,
        ],[
            "ip_latitude" => 28.5978,
            "ip_longitude" => -81.3024,
            "ip_country_code" => "US",
            "ip_country_name" => "United States",
            "ip_region" => "Florida",
            "ip_city" => "Winter Park",
            "ip_postal_zip_code" => "32792",
            "ip_timezone" => "America/New_York",
            "ip_currency" => "USD",
            "private" => false,
            "failed" => false,
        ],[
            "ip_latitude" => 51.2993,
            "ip_longitude" => 9.491,
            "ip_country_code" => "DE",
            "ip_country_name" => "Germany",
            "ip_region" => null,
            "ip_city" => null,
            "ip_postal_zip_code" => null,
            "ip_timezone" => "Europe/Berlin",
            "ip_currency" => "EUR",
            "private" => false,
            "failed" => false,
        ],[
            "ip_latitude" => 41.4098,
            "ip_longitude" => -73.59,
            "ip_country_code" => "US",
            "ip_country_name" => "United States",
            "ip_region" => "New York",
            "ip_city" => "Brewster",
            "ip_postal_zip_code" => "10509",
            "ip_timezone" => "America/New_York",
            "ip_currency" => "USD",
            "private" => false,
            "failed" => false,
        ],[
            "ip_latitude" => 34.7487,
            "ip_longitude" => -80.7595,
            "ip_country_code" => "US",
            "ip_country_name" => "United States",
            "ip_region" => "South Carolina",
            "ip_city" => "Lancaster",
            "ip_postal_zip_code" => "29720",
            "ip_timezone" => "America/New_York",
            "ip_currency" => "USD",
            "private" => false,
            "failed" => false,
        ],[
            "ip_latitude" => 50.0164,
            "ip_longitude" => 8.4485,
            "ip_country_code" => "DE",
            "ip_country_name" => "Germany",
            "ip_region" => "Hesse",
            "ip_city" => "Raunheim",
            "ip_postal_zip_code" => "65479",
            "ip_timezone" => "Europe/Berlin",
            "ip_currency" => "EUR",
            "private" => false,
            "failed" => false,
        ],[
            "ip_latitude" => 44.0197,
            "ip_longitude" => -123.1008,
            "ip_country_code" => "US",
            "ip_country_name" => "United States",
            "ip_region" => "Oregon",
            "ip_city" => "Eugene",
            "ip_postal_zip_code" => "97405",
            "ip_timezone" => "America/Los_Angeles",
            "ip_currency" => "USD",
            "private" => false,
            "failed" => false,
        ],[
            "ip_latitude" => 30.1655,
            "ip_longitude" => -85.7116,
            "ip_country_code" => "US",
            "ip_country_name" => "United States",
            "ip_region" => "Florida",
            "ip_city" => "Panama City",
            "ip_postal_zip_code" => "32408",
            "ip_timezone" => "America/Chicago",
            "ip_currency" => "USD",
            "private" => false,
            "failed" => false,
        ],[
            "ip_latitude" => 37.751,
            "ip_longitude" => -97.822,
            "ip_country_code" => "US",
            "ip_country_name" => "United States",
            "ip_region" => null,
            "ip_city" => null,
            "ip_postal_zip_code" => null,
            "ip_timezone" => "America/Chicago",
            "ip_currency" => "USD",
            "private" => false,
            "failed" => false,
        ],[
            "ip_latitude" => 26.8238,
            "ip_longitude" => -80.1407,
            "ip_country_code" => "US",
            "ip_country_name" => "United States",
            "ip_region" => "Florida",
            "ip_city" => "Palm Beach Gardens",
            "ip_postal_zip_code" => "33418",
            "ip_timezone" => "America/New_York",
            "ip_currency" => "USD",
            "private" => false,
            "failed" => false,
        ],[
            "ip_latitude" => 37.9842,
            "ip_longitude" => 23.7353,
            "ip_country_code" => "GR",
            "ip_country_name" => "Greece",
            "ip_region" => "Attica",
            "ip_city" => "Athens",
            "ip_postal_zip_code" => null,
            "ip_timezone" => "Europe/Athens",
            "ip_currency" => "EUR",
            "private" => false,
            "failed" => false,
        ],[
            "ip_latitude" => 51.4232,
            "ip_longitude" => 7.0298,
            "ip_country_code" => "DE",
            "ip_country_name" => "Germany",
            "ip_region" => "North Rhine-Westphalia",
            "ip_city" => "Essen",
            "ip_postal_zip_code" => "45134",
            "ip_timezone" => "Europe/Berlin",
            "ip_currency" => "EUR",
            "private" => false,
            "failed" => false,
        ],[
            "ip_latitude" => 40.7667,
            "ip_longitude" => -82.5356,
            "ip_country_code" => "US",
            "ip_country_name" => "United States",
            "ip_region" => "Ohio",
            "ip_city" => "Mansfield",
            "ip_postal_zip_code" => "44903",
            "ip_timezone" => "America/New_York",
            "ip_currency" => "USD",
            "private" => false,
            "failed" => false,
        ],[
            "ip_latitude" => 36.0964,
            "ip_longitude" => -86.8212,
            "ip_country_code" => "US",
            "ip_country_name" => "United States",
            "ip_region" => "Tennessee",
            "ip_city" => "Nashville",
            "ip_postal_zip_code" => "37215",
            "ip_timezone" => "America/Chicago",
            "ip_currency" => "USD",
            "private" => false,
            "failed" => false,
        ],[
            "ip_latitude" => 45.3575,
            "ip_longitude" => 11.7872,
            "ip_country_code" => "IT",
            "ip_country_name" => "Italy",
            "ip_region" => "Veneto",
            "ip_city" => "Abano Terme",
            "ip_postal_zip_code" => "35031",
            "ip_timezone" => "Europe/Rome",
            "ip_currency" => "EUR",
            "private" => false,
            "failed" => false,
        ],[
            "ip_latitude" => 42.6563,
            "ip_longitude" => -83.1231,
            "ip_country_code" => "US",
            "ip_country_name" => "United States",
            "ip_region" => "Michigan",
            "ip_city" => "Rochester",
            "ip_postal_zip_code" => "48307",
            "ip_timezone" => "America/Detroit",
            "ip_currency" => "USD",
            "private" => false,
            "failed" => false,
        ],[
            "ip_latitude" => 50.9266,
            "ip_longitude" => -113.9726,
            "ip_country_code" => "CA",
            "ip_country_name" => "Canada",
            "ip_region" => "Alberta",
            "ip_city" => "Calgary",
            "ip_postal_zip_code" => "T2Z",
            "ip_timezone" => "America/Edmonton",
            "ip_currency" => "CAD",
            "private" => false,
            "failed" => false,
        ],[
            "ip_latitude" => 21.1291,
            "ip_longitude" => -101.6737,
            "ip_country_code" => "MX",
            "ip_country_name" => "Mexico",
            "ip_region" => "Guanajuato",
            "ip_city" => "León",
            "ip_postal_zip_code" => "37000",
            "ip_timezone" => "America/Mexico_City",
            "ip_currency" => "MXN",
            "private" => false,
            "failed" => false,
        ],[
            "ip_latitude" => 51.5237,
            "ip_longitude" => -0.089,
            "ip_country_code" => "GB",
            "ip_country_name" => "United Kingdom",
            "ip_region" => "England",
            "ip_city" => "London",
            "ip_postal_zip_code" => "EC2A",
            "ip_timezone" => "Europe/London",
            "ip_currency" => "GBP",
            "private" => false,
            "failed" => false,
        ],[
            "ip_latitude" => 47.3664,
            "ip_longitude" => 8.5546,
            "ip_country_code" => "CH",
            "ip_country_name" => "Switzerland",
            "ip_region" => "Zurich",
            "ip_city" => "Zurich",
            "ip_postal_zip_code" => "8041",
            "ip_timezone" => "Europe/Zurich",
            "ip_currency" => "CHF",
            "private" => false,
            "failed" => false,
        ],[
            "ip_latitude" => 37.751,
            "ip_longitude" => -97.822,
            "ip_country_code" => "US",
            "ip_country_name" => "United States",
            "ip_region" => null,
            "ip_city" => null,
            "ip_postal_zip_code" => null,
            "ip_timezone" => "America/Chicago",
            "ip_currency" => "USD",
            "private" => false,
            "failed" => false,
        ],[
            "ip_latitude" => 44.6707,
            "ip_longitude" => -93.2588,
            "ip_country_code" => "US",
            "ip_country_name" => "United States",
            "ip_region" => "Minnesota",
            "ip_city" => "Lakeville",
            "ip_postal_zip_code" => "55044",
            "ip_timezone" => "America/Chicago",
            "ip_currency" => "USD",
            "private" => false,
            "failed" => false,
        ],[
            "ip_latitude" => 40.8364,
            "ip_longitude" => -74.1403,
            "ip_country_code" => "US",
            "ip_country_name" => "United States",
            "ip_region" => "New Jersey",
            "ip_city" => "Clifton",
            "ip_postal_zip_code" => "07014",
            "ip_timezone" => "America/New_York",
            "ip_currency" => "USD",
            "private" => false,
            "failed" => false,
        ]
    ];

    /**
     * @var DatabaseManager
     */
    private $databaseManager;

    /**
     * @var IpDataApiSdkService
     */
    private $ipDataApiSdkService;

    /**
     * @var array
     */
    private static $tableColumnMap = [
        'ip_latitudes' => 'ip_latitude',
        'ip_longitudes' => 'ip_longitude',
        'ip_country_codes' => 'ip_country_code',
        'ip_country_names' => 'ip_country_name',
        'ip_regions' => 'ip_region',
        'ip_cities' => 'ip_city',
        'ip_postal_zip_codes' => 'ip_postal_zip_code',
        'ip_timezones' => 'ip_timezone',
        'ip_currencies' => 'ip_currency',
    ];

    private $tempTable;

    private $requestsTable;

    /**
     * ProcessTrackings constructor.
     * @param DatabaseManager $databaseManager
     * @param IpDataApiSdkService $ipDataApiSdkService
     */
    public function __construct(
        DatabaseManager $databaseManager,
        IpDataApiSdkService $ipDataApiSdkService
    )
    {
        parent::__construct();
        $this->databaseManager = $databaseManager;
        $this->ipDataApiSdkService = $ipDataApiSdkService;

        $this->requestsTable = config('railtracker.table_prefix') . 'requests';
        $this->tempTable = config('railtracker.table_prefix') . 'geo_ip_intermediary_lib';
    }

    /**
     * @uses tempTableAdd
     * @uses tempTableFill
     * @uses updateRequests
     * return true
     */
    public function handle()
    {
        $explodedSig = explode(' ', $this->signature);
        $signatureStart = reset($explodedSig);

        $validOptionSupplied = in_array(
            $this->argument('selection'),
            array_keys(self::$ACTIONS),
            !is_numeric($this->argument('selection'))
        );

        if(!$validOptionSupplied){

            $this->info('Please try again, specifying an option from the list below:');

            foreach(self::$ACTIONS as $key => $value){
                $this->info('    ' . $key . '. ' . $value);
            }

            $randOption = rand(0,(count(self::$ACTIONS)-1));

            $this->info(
                'Ex: `artisan ' . $signatureStart . ' ' . $randOption . '` if you want the "' .
                self::$ACTIONS[$randOption] . '" option'
            );

            return true;
        }

        $methodToRun = self::$ACTIONS[$this->argument('selection')];

        $timeString = Carbon::now()->timezone('America/Vancouver')->toDateTimeString();

        $this->info($timeString . ', starting "' . $methodToRun . '"');

        $this->info('');

        $this->$methodToRun();
        return true;
    }

    /**
     * @param int $microSec
     * @return void
     */
    private function pause($microSec = 25000)
    {
        $envIsDev = getenv()['APP_ENV'] === 'development';

        if(!$envIsDev || self::$PAUSE_ON_LOCAL){
            usleep($microSec);
        }
    }

    /**
     * @param array $items
     * @return void
     */
    private function print(...$items)
    {
        $str = '';
        foreach($items as $item){
            $str = $str . $item . ',';
        }
        $this->info(rtrim($str,','));
    }

    private function tempTableAdd()
    {
        // todo: are minforchunk & maxforchunk good? check that we're not missing one or two from start|end every chunk

        $idFilterMarker = 0;
        $oneThousand = 1000;
        $oneMillion = 1000000;
        $maxId = config('railtracker.fix_missing_ip_data_max_id', $oneMillion * 16);
        $whileChunkSize = config('railtracker.fix_missing_ip_data_while_chunk_size', 10 * $oneThousand);
        $chunkCount = 0;
        $keepGoing = true;

        $this->print('chunkCount,totalResults,uniqueResults,countBefore,countAfter,successfulInsert');

        while ($keepGoing) {
            $ipAddresses = [];
            $dataToInsert = [];
            $chunkCount++;
            $idMinForChunk = $idFilterMarker;
            $idFilterMarker = $idFilterMarker + $whileChunkSize;
            $idMaxForChunk = $idFilterMarker;
            $keepGoing = $idFilterMarker < $maxId;

            $this->pause();
            $timestamp = Carbon::now()->toDateTimeString();
            $chunkResults = $this->databaseManager
                ->table($this->requestsTable)
                ->select('id', 'ip_address')
                ->where(['ip_longitude' => null, 'ip_latitude' => null])
                ->whereNotNull('ip_address')
                ->where('id', '>', $idMinForChunk)
                ->where('id', '<', $idMaxForChunk)
                ->groupBy('ip_address', 'id')
                ->orderBy('id')
                ->get();

            if($chunkResults->isEmpty()) continue;

            foreach($chunkResults as $row){
                $ipAddresses[] = $row->ip_address;
            }

            $totalResults = count($ipAddresses);

            $ipAddresses = array_unique($ipAddresses);

            $uniqueResults = count($ipAddresses);

            $this->pause();

            $exists = $this->databaseManager->connection()
                ->table($this->tempTable)
                ->whereIn('ip_address', $ipAddresses)
                ->get();

            $countBefore = count($ipAddresses);

            foreach($exists as $existentRecord){

                $rowExistsForThisIpAddress = $existentRecord->ip_address;

                foreach($ipAddresses as $key => $ipAddress){

                    $keyToRemove = null;

                    if($ipAddress === $rowExistsForThisIpAddress){
                        $keyToRemove = $key;
                    }

                    if(!is_null($keyToRemove)){
                        unset($ipAddresses[$keyToRemove]);
                    }
                }
            }

            $countAfter = count($ipAddresses);

            // ---------------------------------------------------------------------------------------------------------

            foreach($ipAddresses as $ipAddress){
                $dataToInsert[] = [
                    'ip_address' => $ipAddress,
                    'created' => $timestamp
                ];
            }

            if(!empty($dataToInsert)){
                $valuesString = '';
                $i = 1;
                foreach($dataToInsert as $row){
                    $last = count($dataToInsert) === $i;
                    if($last){
                        $valuesString = $valuesString . '("' . $row['ip_address'] . '","' . $row['created'] . '")';
                    }else{
                        $valuesString = $valuesString . '("' . $row['ip_address'] . '","' . $row['created'] . '"),';
                    }
                    $i++;
                }

                $this->pause();
                $string = 'INSERT IGNORE INTO '. $this->tempTable .' (ip_address, created) VALUES' . $valuesString;
                $bulkInsertResult = $this->databaseManager->connection()->insert($string);
                $successfulInsert = $bulkInsertResult ? 'true' : 'FALSE';
            }

            $this->print($chunkCount, $totalResults, $uniqueResults, $countBefore, $countAfter, $successfulInsert ?? null);
        }
        return true;
    }

    private function tempTableFill()
    {
        $chunkSize = IpDataApiSdkService::$apiBulkLimit;

        if(self::$DEV_MODE){                            // TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP
            $this->info('');                            // TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP
            $this->info('');                            // TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP
            $this->info('self::$DEV_MODE is true (!!)');// TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP
            $this->info('self::$DEV_MODE is true (!!)');// TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP
            $this->info('self::$DEV_MODE is true (!!)');// TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP
            $this->info('self::$DEV_MODE is true (!!)');// TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP
            $this->info('');                            // TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP
            $this->info('');                            // TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP
            $chunkSize = 50;                            // TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP
        }

        $chunkCount = 0;

        $this->databaseManager->connection()
            ->table($this->tempTable)
            ->select('id', 'ip_address')
            ->whereNull('filled')
            ->chunkById($chunkSize, function($rows) use (&$chunkCount){

                $chunkCount++;

                if(self::$DEV_MODE) {   // TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP
                    if ($chunkCount === 1) return true; // TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP
                }

                $idsByIpAddress = [];
                $rowsToInsert = [];

                foreach($rows as $row){
                    $idsByIpAddress[$row->ip_address][] = $row->id;
                }

                if(!self::$DEV_MODE){
                    $ipData = $this->ipDataApiSdkService->bulkRequest(array_keys($idsByIpAddress));
                }


                if(!self::$DEV_MODE){
                    $rowsToInsert = self::$DEV_AID_1;
                }else{



                    dump('LOSE!!!!!!'); // TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP
                    dump('LOSE!!!!!!'); // TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP
                    dump('LOSE!!!!!!'); // TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP
                    dump('LOSE!!!!!!'); // TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP
                    dump('LOSE!!!!!!'); // TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP
//                    die();    // TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP




                    $ipData = $this->ipDataApiSdkService->bulkRequest(array_keys($idsByIpAddress));

                    $rowsToInsert = [];

                    foreach($ipData as $ipDatum){

                        $privateIp = false;

                        if(!empty($ipDatum['message'])) {
                            $message = $ipDatum['message'];
                            $privateIp = count($ipDatum) && (strpos($message, ' is a private IP address') !== false);
                            if($privateIp){
                                $ipWhenResultSaysPrivate = rtrim($message, ' is a private IP address');
                                $this->info('$ipWhenResultSaysPrivate: ' . $ipWhenResultSaysPrivate);
                            }
                        }

                        $ip = (!$privateIp) ? ($ipDatum['ip'] ?? null) : ($ipWhenResultSaysPrivate ?? null);

                        $rowToInsert = [
                            'ip_latitude' => $ipDatum['latitude'] ?? null,
                            'ip_longitude' => $ipDatum['longitude'] ?? null,
                            'ip_country_code' => $ipDatum['country_code'] ?? null,
                            'ip_country_name' => $ipDatum['country_name'] ?? null,
                            'ip_region' => $ipDatum['region'] ?? null,
                            'ip_city' => $ipDatum['city'] ?? null,
                            'ip_postal_zip_code' => $ipDatum['postal'] ?? null,
                            'ip_timezone' => $ipDatum['time_zone']->name ?? null,
                            'ip_currency' => $ipDatum['currency']->code ?? null,
                            'private' => $privateIp,
                        ];

                        // determine if not private but didn't get info
                        $rowToInsertCopy = $rowToInsert;
                        unset($rowToInsertCopy['private']);
                        $allEmpty = true;
                        foreach($rowToInsertCopy as $field){
                            if(!empty($field)) $allEmpty = false;
                        }

                        $rowToInsert['failed'] = $allEmpty;

                        if(!empty($rowsToInsert[$ip])){
                            $this->info(
                                'Warning! "$rowsToInsert[$ip]" is already set for ' . $ip .
                                '. This should not be possible. The one already set is: '
                            );
                            $this->info(var_export($rowsToInsert[$ip], true));
                            $this->info('The new one is:');
                            $this->info(var_export($rowToInsert, true));
                        }

                        $rowsToInsert[$ip] = $rowToInsert;
                    }
                }

                // todo: insert here (PICK UP HERE)
                // todo: insert here (PICK UP HERE)
                // todo: insert here (PICK UP HERE)
                // todo: insert here (PICK UP HERE)
                // todo: insert here (PICK UP HERE)
                // todo: insert here (PICK UP HERE)

                // update DB "geo_ip_intermediary_lib"

                dd($rowsToInsert);

            }); // end of chunk

        // end of "tempTableFill" method
    }

    private function updateRequests()
    {

    }

    //private static $dataForDev =

}
