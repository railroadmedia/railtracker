<?php

namespace Railroad\Railtracker\Events;

class MediaPlaybackTracked
{
    /**
     * @var integer
     */
    public $id;

    /**
     * @var integer
     */
    public $mediaId;

    /**
     * @var integer
     */
    public $mediaLengthInSeconds;

    /**
     * @var integer
     */
    public $userId;

    /**
     * @var integer
     */
    public $typeId;

    /**
     * @var integer
     */
    public $secondsPlayed;

    /**
     * @var integer
     */
    public $currentSecond;

    /**
     * @var string
     */
    public $startedOn;

    /**
     * @var string
     */
    public $lastUpdatedOn;
    /**
     * @var string
     */
    public $brand;
    /**
     * @var integer
     */
    public $contentId;

    /**
     * MediaPlaybackTracked constructor.
     *
     * @param $id
     * @param $mediaId
     * @param $mediaLengthInSeconds
     * @param int|null $userId
     * @param $typeId
     * @param $secondsPlayed
     * @param $currentSecond
     * @param $startedOn
     * @param $lastUpdatedOn
     */
    public function __construct(
        $id,
        $mediaId,
        $mediaLengthInSeconds,
        $userId,
        $typeId,
        $secondsPlayed,
        $currentSecond,
        $startedOn,
        $lastUpdatedOn,
        $brand,
        $contentId = null
    ) {
        $this->id = $id;
        $this->mediaId = $mediaId;
        $this->mediaLengthInSeconds = $mediaLengthInSeconds;
        $this->userId = $userId;
        $this->typeId = $typeId;
        $this->secondsPlayed = $secondsPlayed;
        $this->currentSecond = $currentSecond;
        $this->brand = $brand;
        $this->startedOn = $startedOn;
        $this->lastUpdatedOn = $lastUpdatedOn;
        $this->contentId = $contentId;
    }
}