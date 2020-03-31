<?php

namespace Railroad\Railtracker\Console\Commands;

use Carbon\Carbon;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Railroad\Railtracker\Repositories\RequestRepository;
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
            $chunkResults = $this->getRequestsRows($idMinForChunk, $idMaxForChunk);

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

        $chunkCount = 0;

        $this->databaseManager->connection()
            ->table($this->tempTable)
            ->select('id', 'ip_address')
            ->whereNull('filled')
            ->chunkById($chunkSize, function($rows) use (&$chunkCount){
                $chunkCount++;
                if(empty($rows)){
                    $this->info($chunkCount === 1 ? 'No rows need filling.' : 'Chunk ' . $chunkCount . ' empty.');
                    return false;
                }
                $idsByIpAddress = [];
                $insertSuccesses = [];

                foreach($rows as $row){
                    $idsByIpAddress[$row->ip_address][] = $row->id;
                }

                $ipData = $this->ipDataApiSdkService->bulkRequest(array_keys($idsByIpAddress));

                foreach($ipData as $ipDatum){

                    $privateIp = false;

                    if(!empty($ipDatum['message'])) {
                        $message = $ipDatum['message'];
                        $privateIp = count($ipDatum) && (strpos($message, ' is a private IP address') !== false);
                        $invalidIpAddress = strpos($message, ' does not appear to be an IPv4 or IPv6 address') !== false;

                        if($privateIp){
                            $ipWhenResultSaysPrivate = rtrim($message, ' is a private IP address');
                            //$this->info('$ipWhenResultSaysPrivate: ' . $ipWhenResultSaysPrivate);
                        }
                    }

                    if($invalidIpAddress ?? false) continue; // these are handled below

                    $ip = (!$privateIp) ? ($ipDatum['ip'] ?? null) : ($ipWhenResultSaysPrivate ?? null);

                    $this->update($privateIp, $rows, $ip, $idsByIpAddress, $insertSuccesses);
                }

                $this->info(
                    'successfully updated ' . count($insertSuccesses ?? []) . ' rows (ids: ' .
                    var_export(implode(', ', $insertSuccesses ?? []), true) . ')'
                );

                if(empty($idsByIpAddress)){
                    $this->info('all processed in this chunk');
                }else{
                    $this->info('Failed to process ' . count($idsByIpAddress) . ' rows. They are: ' .
                        var_export($idsByIpAddress, true)
                    );
                    $this->info('Some of them may be invalid ip addresses. Processing those now');
                    foreach($idsByIpAddress as $ip => $ids){
                        $success = $this->update(false, $rows, $ip, $idsByIpAddress, $insertSuccesses);
                        $this->info('processing ip "' . $ip . '" ' . ($success ? 'succeeded' : 'failed'));
                    }
                }

                if(!empty($idsByIpAddress)){
                    $this->info('there\'s still still some that failed be processed');
                    $this->info(var_export($idsByIpAddress, true));
                    $stop = !$this->confirm('Do you wish to continue?');
                    if($stop){
                        return false;
                    }
                }

                $this->info('');
                return true;
            }); // end of chunk

        // end of "tempTableFill" method
    }

    private function update($privateIp, $rows, $ip, &$idsByIpAddress, &$insertSuccesses)
    {
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
            'filled' => Carbon::now()->toDateTimeString(),
        ];

        // determine if not private but didn't get info
        $rowToInsertCopy = $rowToInsert;
        unset($rowToInsertCopy['private']);
        $allEmpty = true;
        foreach($rowToInsertCopy as $field){
            if(!empty($field)) $allEmpty = false;
        }

        $rowToInsert['failed'] = $allEmpty;

        $id = null;
        foreach($rows as $row){
            if($row->ip_address === $ip) $id = $row->id;
        }
        if(empty($id)){
            $this->info('failed to match id to rowToInsert. This is weird and unexpected');
            return false;
        }

        $successfulInsert = $this->databaseManager->table($this->tempTable)
            ->where('id', $id)
            ->update($rowToInsert);

        if($successfulInsert) {
            unset($idsByIpAddress[$ip]);
            $insertSuccesses[] = $id;
        }else{
            $this->info(
                'Insert failed for id: ' . $id . ' with values: ' .
                $this->info(var_export($rowToInsert, true))
            );
        }

        return true;
    }

    private function getRequestsRows($idMinForChunk, $idMaxForChunk)
    {
        return $this->databaseManager
            ->table($this->requestsTable)
            ->select('id', 'ip_address')
            ->where(['ip_longitude' => null, 'ip_latitude' => null])
            ->whereNotNull('ip_address')
            ->where('id', '>', $idMinForChunk)
            ->where('id', '<', $idMaxForChunk)
            ->groupBy('ip_address', 'id')
            ->orderBy('id')
            ->get();
    }

    private function updateRequests()
    {
        $idFilterMarker = 0;
        $maxId = config('railtracker.fix_missing_ip_data_max_id', 1000 * 1000 * 16);
        $whileChunkSize = 1000;
        $chunkCount = 0;
        $keepGoing = true;

        //$this->print('chunkCount,totalResults,uniqueResults,countBefore,countAfter,successfulInsert');

        while ($keepGoing) {
            $chunkCount++;
            $idMinForChunk = $idFilterMarker;
            $idFilterMarker = $idFilterMarker + $whileChunkSize;
            $idMaxForChunk = $idFilterMarker;
            $keepGoing = $idFilterMarker < $maxId;

            $this->pause();
            $timestamp = Carbon::now()->toDateTimeString();
            $chunkResults = $this->getRequestsRows($idMinForChunk, $idMaxForChunk);

            if($chunkResults->isEmpty()) continue;
            foreach($chunkResults as $row){
                $ipAddresses[] = $row->ip_address;
            }

            $ipData = $this->databaseManager->connection()
                ->table($this->tempTable)
                ->whereIn('ip_address', $ipAddresses ?? [])
                ->get();

            $associationTables = [
                'ip_addresse',
                'ip_latitude',
                'ip_longitude',
                'ip_country_code',
                'ip_country_name',
                'ip_region',
                'ip_citie',
                'ip_postal_zip_code',
                'ip_timezone',
                'ip_currencie',
            ];

            // todo: pick up here, check that this works, and that it makes sense, and then work it out
            foreach($ipData as $datum){
                foreach($associationTables as $associationTable){
                    $detailsForTable = RequestRepository::$rowsToInsertByTable[$associationTable];
                    foreach($detailsForTable as $aRow){
                        foreach($aRow as $columnName => $arrayKey){
                            $valuesRequiredInAssociationTable[$associationTable][] = $datum[$arrayKey];
                        }
                    }
                }
            }



            // todo: pick up here make inserts for $valuesRequiredInAssociationTable


        }
    }

    //private static $dataForDev =

}
