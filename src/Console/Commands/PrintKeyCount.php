<?php

namespace Railroad\Railtracker\Console\Commands;

use Illuminate\Http\Request;
use Railroad\Railtracker\Services\BatchService;
use Railroad\Railtracker\Trackers\ExceptionTracker;
use Railroad\Railtracker\Trackers\RequestTracker;
use Railroad\Railtracker\Trackers\ResponseTracker;

class PrintKeyCount extends \Illuminate\Console\Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'PrintKeyCount';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'PrintKeyCount';

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
        $requestKeys = $this->batchService->cache()->keys($this->batchService->batchKeyPrefix . 'request*');

        $this->info(count($requestKeys) . ' request-response-pairs retrieved.');
    }
}
