<?php

namespace Railroad\Railtracker\Console\Commands;

use Railroad\Railtracker\Services\BatchService;

class EmptyLocalCache extends \Illuminate\Console\Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'EmptyLocalCache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Empty Local Cache';

    /**
     * @var BatchService
     */
    private $batchService;

    public function __construct(
        BatchService $batchService
    )
    {
        parent::__construct();

        $this->batchService = $batchService;
    }

    public function handle()
    {
        $allKeysForPrefix = $this->batchService->cache()->keys($this->batchService->batchKeyPrefix . '*');

        $this->info('Count before: ' . count($allKeysForPrefix));

        $this->batchService->connection()->del($allKeysForPrefix);

        $allKeysForPrefix = $this->batchService->connection()->keys($this->batchService->batchKeyPrefix . '*');

        if(empty($allKeysForPrefix)){
            $this->info('Mass key-deletion successful.');
        }else{
            $this->info('Mass key-deletion failed.');
        }

        $allKeysForPrefix = $this->batchService->connection()->keys($this->batchService->batchKeyPrefix . '*');

        $this->info('Count after: ' . count($allKeysForPrefix));
    }
}
