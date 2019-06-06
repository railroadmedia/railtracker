<?php

namespace Railroad\Railtracker\Console\Commands;

use Exception;
use Railroad\Railtracker\Entities\RequestAgent;
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
        $chunkSize = 100;
        $redisIterator = null;
        $allKeys = [];

        $processedCount = 0;
        $errorsCount = 0;

        while ($redisIterator !== 0) {
            $requestKeys =
                $this->batchService->cache()
                    ->scan($redisIterator, ['MATCH' => $this->batchService->batchKeyPrefix . '*', 'COUNT' => 1000]);

            $redisIterator = (integer)$requestKeys[0];

            $keysThisChunk = $requestKeys[1];
            $valuesThisChunk = [];

            foreach ($keysThisChunk as $keyThisChunk) {
                $valuesThisChunk = array_merge(
                    $valuesThisChunk,
                    $this->batchService->cache()
                        ->smembers($keyThisChunk)
                );
            }

            $agentsByHash = [];

            foreach ($valuesThisChunk as $valueThisChunk) {
                $unserialized = unserialize($valueThisChunk);

                if (isset($unserialized['type']) && $unserialized['type'] == 'request') {
                    $agentsByHash[$unserialized['agent']['hash']] = $unserialized['agent'];
                }
            }

            $qb = $this->entityManager->createQueryBuilder();

            /**
             * @var $existingRequestAgents RequestAgent[]
             */
            $existingRequestAgents =
                $qb->select('ra')
                    ->from(RequestAgent::class, 'ra')
                    ->where(
                        'ra.hash IN (:hashes)'
                    )
                    ->setParameter('hashes', array_keys($agentsByHash))
                    ->getQuery()
                    ->getResult();

            /**
             * @var $existingRequestAgentsByHash RequestAgent[]
             */
            $existingRequestAgentsByHash = [];

            foreach ($existingRequestAgents as $existingRequestAgent) {
                $existingRequestAgentsByHash[$existingRequestAgent->getHash()] = $existingRequestAgent;
            }

            $requestAgentEntities = [];

            foreach ($agentsByHash as $agentHash => $agentByHash) {

                if (!isset($existingRequestAgentsByHash[$agentHash])) {

                    $agentData = $agentsByHash[$agentHash];

                    $requestAgentEntity = new RequestAgent();


                    $requestAgentEntity->setName($agentData['name']);
                    $requestAgentEntity->setBrowserVersion($agentData['browserVersion']);
                    $requestAgentEntity->setBrowser($agentData['browser']);
                    $requestAgentEntity->setHash();

                    $requestAgentEntities[$requestAgentEntity->getHash()] = $requestAgentEntity;

                    $this->entityManager->persist($requestAgentEntity);

                }

            }

            $this->entityManager->flush();

            dd($requestAgentEntities);
        }

//        foreach (array_chunk($requestKeys, $chunkSize) as $requestKeysChunk) {
//
//            foreach ($requestKeysChunk as $requestKey) {
//
//                try {
//                    $requestData = unserialize(
//                        $this->batchService->cache()
//                            ->get($requestKey)
//                    );
//
//                    $uuid = $requestData['uuid'];
//
//                    $exceptionKey = $this->batchService->batchKeyPrefix . 'exception_' . $uuid;
//                    $responseKey = $this->batchService->batchKeyPrefix . 'response_' . $uuid;
//
//                    $exceptionData = unserialize(
//                        $this->batchService->cache()
//                            ->get($exceptionKey)
//                    );
//                    $responseData = unserialize(
//                        $this->batchService->cache()
//                            ->get($responseKey)
//                    );
//
//                    $requestEntity = $this->requestTracker->process($requestData);
//
//                    if (!empty($exceptionData)) {
//                        $this->exceptionTracker->process($exceptionData, $requestEntity);
//                    }
//
//                    $this->responseTracker->process($responseData, $requestEntity);
//
//                    $this->entityManager->clear();
//
//                } catch (Exception $exception) {
//                    error_log($exception);
//                    $errorsCount++;
//                }
//                $this->batchService->forget($requestKey);
//                $this->batchService->forget($exceptionKey ?? null);
//                $this->batchService->forget($responseKey ?? null);
//
//                $processedCount++;
//            }
//        }
//
//        $msg =
//            'Processed ' .
//            $processedCount .
//            ' requests (and their responses and sometimes exceptions). ' .
//            'No problems arose during processing';
//
//        if ($errorsCount > 0) {
//            $msg =
//                'Processed ' .
//                $processedCount .
//                ' requests (and their responses and sometimes exceptions). ' .
//                $errorsCount .
//                ' errors caught in ProcessTracking\'s try-catch.';
//        }
//
//        $this->info($msg);

        return true;
    }
}
