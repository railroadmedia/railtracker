<?php

namespace Railroad\Railtracker\Trackers;

use Exception;
use Railroad\Railtracker\Services\ConfigService;

class ExceptionTracker extends TrackerBase
{
    /**
     * @var int|null
     */
    public static $lastCreatedErrorId;

    /**
     * @param Exception $exception
     * @param bool $attachRequest
     * @return int|null
     */
    public function trackException(Exception $exception, $attachRequest = true)
    {
        $exceptionId = $this->storeAndCache(
            [
                'code' => $exception->getCode(),
                'line' => $exception->getLine(),
                'exception_class' => get_class($exception),
                'file' => $exception->getFile(),
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ],
            ConfigService::$tableExceptions
        );

        if ($attachRequest && !empty(RequestTracker::$lastTrackedRequestId)) {
            $this->trackRequestException($exceptionId, RequestTracker::$lastTrackedRequestId);
        }

        self::$lastCreatedErrorId = $exceptionId;

        return $exceptionId;
    }

    /**
     * @param $exceptionId
     * @param $requestId
     * @return int
     */
    public function trackRequestException($exceptionId, $requestId)
    {
        $data = [
            'exception_id' => $exceptionId,
            'request_id' => $requestId,
            'created_at_timestamp_ms' => round(microtime(true) * 1000),
        ];

        return $this->query(ConfigService::$tableRequestExceptions)->insertGetId($data);
    }
}