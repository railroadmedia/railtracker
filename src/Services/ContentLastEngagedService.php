<?php

namespace Railroad\Railtracker\Services;

use Railroad\Railtracker\Repositories\ContentLastEngagedRepository;

class ContentLastEngagedService
{
    private ContentLastEngagedRepository $contentLastEngagedRepository;

    public function __construct(ContentLastEngagedRepository $contentLastEngagedRepository)
    {
        $this->contentLastEngagedRepository = $contentLastEngagedRepository;
    }

    /**
     * @param $userId
     * @param $contentId
     * @param null $parentPlaylistId
     * @param null $parentContentId
     * @return \Illuminate\Support\Collection
     */
    public function engageContent($userId, $contentId, $parentPlaylistId = null, $parentContentId = null)
    {
        return $this->contentLastEngagedRepository->insertOrUpdate(
            $userId,
            $contentId,
            $parentPlaylistId,
            $parentContentId
        );
    }

    /**
     * @param $userId
     * @param null $parentPlaylistId
     * @param null $parentContentId
     * @return int
     */
    public function deleteEngagedContent($userId, $parentPlaylistId = null, $parentContentId = null)
    {
        return $this->contentLastEngagedRepository->delete(
            $userId,
            $parentPlaylistId,
            $parentContentId
        );
    }

    public function getLastEngagedContentForPlaylistId($userId, $parentPlaylistId){
        return $this->contentLastEngagedRepository->getLastEngagedContentForPlaylistId($userId, $parentPlaylistId);
    }

}