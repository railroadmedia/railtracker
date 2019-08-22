<?php

namespace Railroad\Railtracker\Console\Commands;

use Railroad\Railtracker\Services\BatchService;

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
