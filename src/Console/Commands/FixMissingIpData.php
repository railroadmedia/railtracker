<?php

namespace Railroad\Railtracker\Console\Commands;

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
    }

    /**
     * return true
     */
    public function handle()
    {
        $table = config('railtracker.table_prefix') . 'requests';

        $updates = [];

        /*
         * All output lines that start with "(csvForTable)," can be copied to "donatstudios.com/CsvToMarkdownTable" for
         * beautification to a markdown table.
         */
        $this->info('(csvForTable),chunk size,request table updates,association tables updates');


        /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *

        This old version is SHIT - it fucking shreks the prod server. Absolute bullshit. Kill it with fire!
        ↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓

        The problem is that it has to query a massive amount of data. with a very fucking broad filter. It gets better
        if we also filter by `ip_address IS NOT NULL`

        * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
//        $this->databaseManager
//            ->table($table)
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


        /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *

        This is the new version below
        ↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓

        To get even better than filtering by `ip_address IS NOT NULL`, **ALSO** filter by id. We *will* have a somewhat
        convoluted loop of going through ids, but oh well, it's better than fucking destroying the DB server for like a
        fucking hour.

        * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

        // consider getting count of table (railtracker4_requests)

        $idFilterMarker = 0;

        // while loop

            // while rows are returned keep going. when we get an empty db result then stop...? NO!! we're filtering
            // ... here so we may sometimes not get results back. Sooooo... we need the max id, and if our
            // "$idFilterMarker" (what we'll to set our min and max values for each query.)

            // when you get results, then process that.






        return true;
    }

    private function fillForIpAddresses(Collection $rowsRequiringData)
    {

        $this->info('processing chunk containing these rows:');
        $this->info('    id,ip_address');

        foreach($rowsRequiringData as $row){
            $this->info('    ' . $row->id . ',' . $row->ip_address);
        }

        $this->info('');

        $idsByIpAddress = [];
        $associationTableUpdateCount = 0;

        foreach($rowsRequiringData as $row){
            $idsByIpAddress[$row->ip_address][] = $row->id;
        }

        $ipData = $this->ipDataApiSdkService->bulkRequest(array_keys($idsByIpAddress));

        // Uncomment ↓ to see "total number of requests made by your API key in the last 24 hrs. Updates once a minute."
        //$this->info('API requests in past 24h: ' . end($ipData)['count'] ?? null);

        dd($ipData);

        foreach($idsByIpAddress as $ipAddress => $rowIds){

            $updates = [];

            // find relevant ip data
            $relevantIpData = null;
            foreach($ipData as $candidate){

                if(empty($candidate['ip'])) continue;

                if($candidate['ip'] === $ipAddress){
                    $relevantIpData = $candidate;
                };
            }

            if(empty($relevantIpData)) continue;

            dd($relevantIpData);

            // prepare ip data for db insertion
            $dataForUpdate = [
                'ip_latitude' => $relevantIpData['latitude'],
                'ip_longitude' => $relevantIpData['longitude'],
                'ip_country_code' => $relevantIpData['country_code'],
                'ip_country_name' => $relevantIpData['country_name'],
                'ip_region' => $relevantIpData['region_code'],
                'ip_city' => $relevantIpData['city'],
                'ip_postal_zip_code' => $relevantIpData['postal'],
                'ip_timezone' => !empty($relevantIpData['time_zone']) ? $relevantIpData['time_zone']->name : null,
                'ip_currency' => !empty($relevantIpData['currency']) ? $relevantIpData['currency']->code : null,
            ];

            // first update association tables
            foreach(self::$tableColumnMap as $table => $column){
                $table = config('railtracker.table_prefix') . $table;

                $valuesToInsert = $dataForUpdate[$column];

                if(empty($valuesToInsert)) continue;

                $preexistingRecord = $this->databaseManager
                    ->table($table)
                    ->select()
                    ->where([$column => $valuesToInsert])
                    ->get()
                    ->first();

                $alreadyExists = !empty($preexistingRecord);

                if($alreadyExists) continue;

                try{
                    $updates[] = $this->databaseManager->table($table)->updateOrInsert([$column => $valuesToInsert]);
                }catch(\Exception $exception){
                    error_log($exception);
                    $this->info(
                        'Association table insert failed for \'' . $table . '\' table. With exception message ' .
                        $exception->getMessage() . '. IP-address: ' . $ipAddress . '. See error log.'
                    );
                }
            }

            $associationTableUpdateCount += count($updates);

            // then update requests table
            $requestTableUpdates[] = $this->databaseManager->table(config('railtracker.table_prefix') . 'requests')
                ->wherein('id', $rowIds)
                ->update($dataForUpdate);
        }

        return [
            'requestTableUpdatesCount' => count($requestTableUpdates ?? []),
            'associationTableUpdateCount' => $associationTableUpdateCount,
        ];
    }
}
