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
     * @param string $requestedAtDateTime
     * @param string|null $usersPreviousRequestedAtDateTime
     */
    public function __construct(
        $requestId,
        $userId,
        $requestedAtDateTime,
        $usersPreviousRequestedAtDateTime = null
    ) {
        $this->requestId = $requestId;
        $this->userId = $userId;
        $this->requestedOnDateTime = $requestedAtDateTime;
        $this->usersPreviousRequestedOnDateTime = $usersPreviousRequestedAtDateTime;
    }
}