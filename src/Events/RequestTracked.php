<?php

namespace Railroad\Railtracker\Events;

class RequestTracked
{
    /**
     * @var int
     */
    public $requestId;

    /**
     * @var int|null
     */
    public $userId;

    /**
     * @var string
     */
    public $clientIp;

    /**
     * @var string
     */
    public $userAgent;

    /**
     * @var string
     */
    public $requestedOnDateTime;

    /**
     * @var null|string
     */
    public $usersPreviousRequestedOnDateTime;

    /**
     * RequestTracked constructor.
     *
     * @param int $requestId
     * @param int|null $userId
     * @param string $clientIp
     * @param string $userAgent
     * @param string $requestedAtDateTime
     * @param string|null $usersPreviousRequestedAtDateTime
     */
    public function __construct(
        $requestId,
        $userId,
        $clientIp,
        $userAgent,
        $requestedAtDateTime,
        $usersPreviousRequestedAtDateTime = null
    ) {
        $this->requestId = $requestId;
        $this->userId = $userId;
        $this->clientIp = $clientIp;
        $this->userAgent = $userAgent;
        $this->requestedOnDateTime = $requestedAtDateTime;
        $this->usersPreviousRequestedOnDateTime = $usersPreviousRequestedAtDateTime;
    }
}