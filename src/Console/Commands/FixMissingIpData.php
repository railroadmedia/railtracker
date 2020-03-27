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
        $this->tempTable = config('railtracker.table_prefix') . 'geo_ip_fix_temp_library';
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

        $this->print('$chunkCount, $totalResults, $uniqueResults, $countBefore, $countAfter,((string) $bulkInsertResult)');

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

            if(empty($dataToInsert)) continue;

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

            $this->print($chunkCount, $totalResults, $uniqueResults, $countBefore, $countAfter,((string) $bulkInsertResult));
        }
        return true;
    }

    private function tempTableFill()
    {

    }

    private function updateRequests()
    {

    }

}
