<?php

namespace Railroad\Railtracker\Trackers;

use Carbon\Carbon;
use Railroad\Railtracker\Services\ConfigService;
use Symfony\Component\HttpFoundation\Response;

class ResponseTracker extends TrackerBase
{
    /**
     * @var int|null
     */
    public static $lastCreatedResponseId;

    /**
     * @param Response $response
     * @param int $requestId
     * @return int|null
     */
    public function trackResponse(Response $response, $requestId)
    {
        if (empty($requestId)) {
            return null;
        }

        $responseStatusCodeId = $this->trackResponseStatusCode($response->getStatusCode());

        $responseId = $this->query(ConfigService::$tableResponses)->insertGetId(
            [
                'request_id' => $requestId,
                'status_code_id' => $responseStatusCodeId,
                'response_duration_ms' => (microtime(true) - LARAVEL_START) * 1000,
                'responded_on' => Carbon::now()->toDateTimeString(),
            ]
        );

        self::$lastCreatedResponseId = $responseId;

        return $responseId;
    }

    /**
     * @param $statusCode
     * @return int
     */
    public function trackResponseStatusCode($statusCode)
    {
        $data = [
            'code' => $statusCode,
        ];

        return $this->store($data, ConfigService::$tableResponseStatusCodes);
    }
}