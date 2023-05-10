<?php

namespace Railroad\Railtracker\Events;

class EngageContent
{
    /**
     * @var int
     */
    public $userId;
    /**
     * @var int
     */
    public $contentId;
    /**
     * @var int
     */
    public $parentContentId;
    /**
     * @var int
     */
    public $parentPlaylistId;

    public function __construct($contentId, $userId, ?int $parentContentId = null, ?int $parentPlaylistId = null)
    {
        $this->contentId = $contentId;
        $this->userId = $userId;
        $this->parentContentId = $parentContentId;
        $this->parentPlaylistId = $parentPlaylistId;
    }
}