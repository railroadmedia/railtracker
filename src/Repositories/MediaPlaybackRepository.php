<?php

namespace Railroad\Railtracker\Repositories;

use Illuminate\Database\Query\JoinClause;

class MediaPlaybackRepository extends TrackerRepositoryBase
{
    /**
     *
     * Returns array with keys are media ids and values as current second for the users latest session.
     *
     * ['media_id_1' => 37, 'media_id_2' => 1512, etc...]
     *
     * @param $mediaType
     * @param $mediaCategory
     * @param array $mediaIds
     * @param $userId
     * @return array
     */
    public function getCurrentSecondForLatestUserMediaSessions(
        $mediaType,
        $mediaCategory,
        array $mediaIds,
        $userId
    ) {
        $rows = $this->databaseManager->connection()->table('railtracker_media_playback_sessions')
            ->leftJoin(
                'railtracker_media_playback_types',
                function (JoinClause $join) {
                    return $join->on(
                        'railtracker_media_playback_types.id',
                        '=',
                        'railtracker_media_playback_sessions.type_id'
                    );
                }
            )
            ->whereIn('railtracker_media_playback_sessions.media_id', $mediaIds)
            ->where('railtracker_media_playback_types.type', $mediaType)
            ->where('railtracker_media_playback_types.category', $mediaCategory)
            ->where('railtracker_media_playback_sessions.user_id', $userId)
            ->whereNotNull('last_updated_on')
            ->select(
                [
                    'railtracker_media_playback_sessions.media_id as media_id',
                    'railtracker_media_playback_sessions.current_second as current_second',
                    $this->databaseManager->connection()->raw(
                        'MAX(railtracker_media_playback_sessions.last_updated_on) as last_updated_on'
                    ),
                ]
            )
            ->groupBy(
                [
                    'last_updated_on',
                    'railtracker_media_playback_sessions.media_id',
                    'railtracker_media_playback_sessions.current_second'
                ]
            )
            ->get();

        $array = $this->collectionToMultiDimensionalArray($rows);

        return array_combine(array_column($array, 'media_id'), array_column($array, 'current_second'));
    }

    /**
     * @param $userId
     * @param $mediaId
     * @param $mediaTypeId
     * @return mixed
     */
    public function sumTotalPlayed($userId, $mediaId, $mediaTypeId)
    {
        return $this->databaseManager->connection()->table('railtracker_media_playback_sessions')
            ->leftJoin(
                'railtracker_media_playback_types',
                function (JoinClause $join) {
                    return $join->on(
                        'railtracker_media_playback_types.id',
                        '=',
                        'railtracker_media_playback_sessions.type_id'
                    );
                }
            )
            ->where('user_id', $userId)
            ->where('railtracker_media_playback_sessions.media_id', $mediaId)
            ->where('railtracker_media_playback_types.id', $mediaTypeId)
            ->sum('seconds_played');
    }
}