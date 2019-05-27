<?php

namespace Railroad\Railtracker\Console\Commands;

use Illuminate\Http\Request;
use Railroad\Railtracker\Services\BatchService;
use Railroad\Railtracker\Trackers\ExceptionTracker;
use Railroad\Railtracker\Trackers\RequestTracker;
use Railroad\Railtracker\Trackers\ResponseTracker;

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

        $this->batchService->cache()->del($allKeysForPrefix);

        $allKeysForPrefix = $this->batchService->cache()->keys($this->batchService->batchKeyPrefix . '*');

        if(empty($allKeysForPrefix)){
            $this->info('Mass key-deletion successful.');
        }else{
            $this->info('Mass key-deletion failed.');
        }

        $allKeysForPrefix = $this->batchService->cache()->keys($this->batchService->batchKeyPrefix . '*');

        $this->info('Count after: ' . count($allKeysForPrefix));
    }
}
