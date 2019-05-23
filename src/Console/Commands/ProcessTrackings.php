<?php

namespace Railroad\Railtracker\Console\Commands;

use DebugBar\DebugBar;
use Exception;
use Illuminate\Support\Facades\App;
use Railroad\Railtracker\Managers\RailtrackerEntityManager;
use Railroad\Railtracker\Services\BatchService;
use Railroad\Railtracker\Trackers\ExceptionTracker;
use Railroad\Railtracker\Trackers\RequestTracker;
use Railroad\Railtracker\Trackers\ResponseTracker;

class ProcessTrackings extends \Illuminate\Console\Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'ProcessTrackings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process items to track.';

    /**
     * @var BatchService
     */
    private $batchService;

    /**
     * @var RequestTracker
     */
    private $requestTracker;

    /**
     * @var ExceptionTracker
     */
    private $exceptionTracker;

    /**
     * @var ResponseTracker
     */
    private $responseTracker;

    /**
     * @var RailtrackerEntityManager
     */
    private $entityManager;

    /**
     * ProcessTrackings constructor.
     * @param BatchService $batchService
     * @param RequestTracker $requestTracker
     * @param ExceptionTracker $exceptionTracker
     * @param ResponseTracker $responseTracker
     * @param RailtrackerEntityManager $entityManager
     */
    public function __construct(
        BatchService $batchService,
        RequestTracker $requestTracker,
        ExceptionTracker $exceptionTracker,
        ResponseTracker $responseTracker,
        RailtrackerEntityManager $entityManager
    )
    {
        parent::__construct();

        $this->batchService = $batchService;
        $this->requestTracker = $requestTracker;
        $this->exceptionTracker = $exceptionTracker;
        $this->responseTracker = $responseTracker;
        $this->entityManager = $entityManager;
    }

    /**
     * return true
     */
    public function handle()
    {
        $requestKeys =
            $this->batchService->cache()
                ->keys($this->batchService->batchKeyPrefix . 'request*');

        $this->info('processing ' . count($requestKeys) . ' requests total');

        $chunkSize = 100;

        $processedCount = 0;
        $errorsCount = 0;

        foreach (array_chunk($requestKeys, $chunkSize) as $requestKeysChunk) {

            foreach ($requestKeysChunk as $requestKey) {

                try {
                    $requestData = unserialize(
                        $this->batchService->cache()
                            ->get($requestKey)
                    );

                    $uuid = $requestData['uuid'];

                    $exceptionKey = $this->batchService->batchKeyPrefix . 'exception_' . $uuid;
                    $responseKey = $this->batchService->batchKeyPrefix . 'response_' . $uuid;

                    $exceptionData = unserialize(
                        $this->batchService->cache()
                            ->get($exceptionKey)
                    );
                    $responseData = unserialize(
                        $this->batchService->cache()
                            ->get($responseKey)
                    );

                    $requestEntity = $this->requestTracker->process($requestData);

                    if (!empty($exceptionData)) {
                        $this->exceptionTracker->process($exceptionData, $requestEntity);
                    }

                    $this->responseTracker->process($responseData, $requestEntity);

                    $this->entityManager->clear();

                } catch (Exception $exception) {
                    error_log($exception);
                    $errorsCount++;
                }
                $this->batchService->forget($requestKey);
                $this->batchService->forget($exceptionKey ?? null);
                $this->batchService->forget($responseKey ?? null);

                $processedCount++;
            }
        }

        $this->info(
            'Processed ' . $processedCount . ' requests (and their responses and sometimes exceptions). ' .
            $errorsCount . ' errors caught in ProcessTracking\'s try-catch.'
        );

        return true;
    }
}
