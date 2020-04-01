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

        // todo: fix this? Its not working I think
        // todo: fix this? Its not working I think
        // todo: fix this? Its not working I think
        // todo: fix this? Its not working I think
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

    private function getRequestsRows($idMinForChunk, $idMaxForChunk, $ipBlacklist = [])
    {
        return $this->databaseManager
            ->table($this->requestsTable)
            ->select('id', 'ip_address')
            ->where(['ip_longitude' => null, 'ip_latitude' => null])
            ->whereNotNull('ip_address')
            ->where('id', '>', $idMinForChunk)
            ->where('id', '<', $idMaxForChunk)
            ->whereNotIn('ip_address', $ipBlacklist)
            ->groupBy('ip_address', 'id')
            ->orderBy('id')
            ->get();
    }

    private function updateRequests()
    {
        /*
         * TL;DR: chunk function of query builder is too expensive. instead "manually" chunk and collect relevant row
         * references into "batches"
         *
         * so, there's:
         *      1. while-chunks, where we gather rows, sometimes get non, so doesn't make sense to operate in these.
         *      2. batches, where we get the relevant data from the interim table and then update railtracker
         *         association and request tables accordingly.
         */

        $idsGroupedByIp = [];
        $ipsWithIdsAndData = [];
        $ipBlacklist = []; // private or invalid IP addresses
        $idFilterMarker = 0;
        $maxId = config('railtracker.fix_missing_ip_data_max_id', 1000 * 1000 * 16);
        $whileChunkSize = 1000;
        $fillingChunkSize = 100;
        //$fillingChunkSize = 20; // TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP
        //$fillingChunkSize = 5; // TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP TEMP
        $chunkCount = 0;
        $keepGoing = true;

        $this->info('');
        $this->info('Gathering rows that require filling');
        $this->info('');

        //$this->print('chunkCount,totalResults,uniqueResults,countBefore,countAfter,successfulInsert');
        $this->info('count($chunkResults),$chunkCount,count($idsGroupedByIp),$allNull,$ipDatum->failed,$ipDatum->private');

        while ($keepGoing) {
            $chunkCount++;
            $idMinForChunk = $idFilterMarker;
            $idFilterMarker = $idFilterMarker + $whileChunkSize;
            $idMaxForChunk = $idFilterMarker;
            $keepGoing = $idFilterMarker < $maxId;

            $this->pause();
            $timestamp = Carbon::now()->toDateTimeString();
            $chunkResults = $this->getRequestsRows($idMinForChunk, $idMaxForChunk, $ipBlacklist);

            if($chunkResults->isEmpty()) continue;

//            $this->info(
//                'Got ' . count($chunkResults) . ' result(s) in chunk ' . $chunkCount .
//                ', $idsGroupedByIp count is now ' . count($idsGroupedByIp)
//            );
            $this->info(
                count($chunkResults) . ',' . $chunkCount . ',' . count($idsGroupedByIp)
            );

            foreach($chunkResults as $row){
                $idsGroupedByIp[$row->ip_address][] = $row->id;
            }

            if(count($idsGroupedByIp) < $fillingChunkSize){
                continue;
            }

            $this->info('');
            $this->info(
                'Proceeding with filling-missing operation now that at least ' . $fillingChunkSize .
                ' ip-addresses collected. (actually: ' . count($idsGroupedByIp) . ')'
            );
            $this->info('');

            $ipData = $this->databaseManager->connection()
                ->table($this->tempTable)
                ->whereIn('ip_address', array_keys($idsGroupedByIp) ?? [])
//                ->whereNotNull('ip_latitude')
//                ->whereNotNull('ip_longitude')
//                ->whereNotNull('ip_country_code')
//                ->whereNotNull('ip_country_name')
//                ->whereNotNull('ip_region')
//                ->whereNotNull('ip_city')
//                ->whereNotNull('ip_postal_zip_code')
//                ->whereNotNull('ip_timezone')
//                ->whereNotNull('ip_currency')
//                ->Where('private', '!=', true)
                //->orWhere('failed', '!=', true)
                ->get()
                ->toArray();

            if(empty($ipData)){
                $idsGroupedByIp = [];
                continue;
            }

            foreach($ipData as $key => $ipDatum){
                $allNull =
                    $ipDatum->ip_latitude === null &&
                    $ipDatum->ip_longitude === null &&
                    $ipDatum->ip_country_code === null &&
                    $ipDatum->ip_country_name === null &&
                    $ipDatum->ip_region === null &&
                    $ipDatum->ip_city === null &&
                    $ipDatum->ip_postal_zip_code === null &&
                    $ipDatum->ip_timezone === null &&
                    $ipDatum->ip_currency === null;

                if($allNull){
                    unset($ipData[$key]);
                }
            }

            if(empty($ipData)){
                continue;
            }

            dump('=========================WIN=========================');
            dump('=========================WIN=========================');
            dump('=========================WIN=========================');
            dump('=========================WIN=========================');
            dump('=========================WIN=========================');
            dump('=========================WIN=========================');
            dump('=========================WIN=========================');
            dump('=========================WIN=========================');
            dump('=========================WIN=========================');
            dd($ipData);

            // todo: pick up here
            // todo: pick up here
            // todo: pick up here
//            foreach($idsGroupedByIp as $ip => $ids) {
//
//            }
        }
    }

    private function processIpsWithIdsAndData($ipsWithIdsAndData)
    {
        dd($ipsWithIdsAndData);
    }

    //private static $dataForDev =

}
