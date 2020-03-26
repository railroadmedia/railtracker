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

    private function tempTableAdd()
    {
        $updates = [];


        /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *

        DONT MAKE THIS MISTAKE AGAIN
        This old version is SHIT - it fucking shreks the prod server. Absolute bullshit. Kill it with fire!
        ↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓

        The problem is that it has to query a massive amount of data. with a very fucking broad filter. It gets better
        if we also filter by `ip_address IS NOT NULL`

        * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
//        $this->databaseManager
//            ->table($this->requestsTable)
//            ->select('id', 'ip_address')
//            ->where(['ip_longitude' => null, 'ip_latitude' => null])
//            ->groupBy('ip_address', 'id')
//            ->orderBy('id')
//            ->chunkById(100, function($ip_addresses) use ($updates){
//                $updateStatusInfo = $this->fillForIpAddresses($ip_addresses);
//                $this->info(
//                    '(csvForTable),' .
//                    count($ip_addresses) . ',' .
//                    $updateStatusInfo['requestTableUpdatesCount'] . ',' .
//                    $updateStatusInfo['associationTableUpdateCount']
//                );
//            });

        $idFilterMarker = 0;

        // while loop
        // =============================================================================================================

        // while rows are returned keep going. when we get an empty db result then stop...? NO!! we're filtering
        // ... here so we may sometimes not get results back. Sooooo... we need the max id, and if our
        // "$idFilterMarker" (what we'll to set our min and max values for each query.)

        // when you get results, then process that.

        $oneThousand = 1000;
        $oneHundredThousand = 100 * $oneThousand;
        $oneMillion = 1000000;

        /*
         * to see number of relevant rows run this query:
         *
         *      SELECT id FROM railtracker4_requests
         *      where ip_address is not null and ip_latitude is null and ip_longitude is null
         *      order by id desc limit 1
         *
         * omit the second line (comment out with `#` to get the highest id in the whole table.
         */

        $maxId = config('railtracker.fix_missing_ip_data_max_id', $oneMillion * 110);

        $whileChunkSize = config('railtracker.fix_missing_ip_data_while_chunk_size', $oneHundredThousand);

        $whileCount = 0;

        $keepGoing = true;

        $this->info('whileCount,idMinForChunk,idMaxForChunk,chunkCount,insertSuccess');

        while ($keepGoing) {

            //usleep(250000);

            $whileCount++;

            $timestamp = Carbon::now()->toDateTimeString();

            //dd($timestamp);

            $idMinForChunk = $idFilterMarker;
            $idFilterMarker = $idFilterMarker + $whileChunkSize;
            $idMaxForChunk = $idFilterMarker;
            $keepGoing = $idFilterMarker < $maxId;
            $this->info($whileCount . ',' . $idMinForChunk . ',' . $idMaxForChunk . ',,');

            $chunkNumber = 0;

            $this->databaseManager
                ->table($this->requestsTable)
                ->select('id', 'ip_address')
                ->where(['ip_longitude' => null, 'ip_latitude' => null])
                ->whereNotNull('ip_address')
                ->where('id', '>', $idMinForChunk)
                ->where('id', '<', $idMaxForChunk)
                ->groupBy('ip_address', 'id')
                ->orderBy('id')
                ->chunkById(100, function($ip_addresses) use ($updates, $timestamp, $idMinForChunk, $idMaxForChunk, &$chunkNumber){
                    usleep(25000);
                    // --------------------------------------- add to temp table ---------------------------------------

                    $chunkNumber++;

                    $dataToInsert = [];

                    foreach($ip_addresses as $row){
                        $dataToInsert[] = [
                            'ip_address' => $row->ip_address,
                            'created' => $timestamp
                        ];
                    }

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

                    // todo: pick up here, either remove 'created' from "where" criteria logic of statement below or just remove that col from table. Probably the latter tbh. It's not worth the effort.
                    // todo: pick up here, either remove 'created' from "where" criteria logic of statement below or just remove that col from table. Probably the latter tbh. It's not worth the effort.
                    // todo: pick up here, either remove 'created' from "where" criteria logic of statement below or just remove that col from table. Probably the latter tbh. It's not worth the effort.
                    // todo: pick up here, either remove 'created' from "where" criteria logic of statement below or just remove that col from table. Probably the latter tbh. It's not worth the effort.
                    // todo: pick up here, either remove 'created' from "where" criteria logic of statement below or just remove that col from table. Probably the latter tbh. It's not worth the effort.

                    $string = 'INSERT IGNORE INTO '. $this->tempTable .' (ip_address, created) VALUES' . $valuesString;

                    $bulkInsertResult = $this->databaseManager->connection()->insert($string);

                    $this->info(',,,' . $chunkNumber . ',' . ($bulkInsertResult ? 'true' : 'false'));

                    return $bulkInsertResult;
                });
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
