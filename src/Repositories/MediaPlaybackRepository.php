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
        $tablePrefix = config('railtracker.table_prefix_media_playback_tracking');
        $typesTable  = $tablePrefix . config('railtracker.media_playback_types', 'media_playback_types');
        $sessionsTable = $tablePrefix . config('railtracker.media_playback_sessions', 'media_playback_sessions');

        $rows = $this->databaseManager->connection(config('railtracker.database_connection_name'))->table($sessionsTable)
            ->leftJoin(
                $typesTable,
                function (JoinClause $join) use ($typesTable, $sessionsTable){
                    return $join->on(
                        $typesTable . '.id',
                        '=',
                        $sessionsTable . '.type_id'
                    );
                }
            )
            ->whereIn($sessionsTable. '.media_id', $mediaIds)
            ->where($typesTable . '.type', $mediaType)
            ->where($typesTable . '.category', $mediaCategory)
            ->where($sessionsTable . '.user_id', $userId)
            ->whereNotNull('last_updated_on')
            ->select(
                [
                    $sessionsTable . '.media_id as media_id',
                    $sessionsTable . '.current_second as current_second',
                    $this->databaseManager->connection()->raw(
                        'MAX(' . $sessionsTable . '.last_updated_on) as last_updated_on'
                    ),
                ]
            )
            ->groupBy(
                [
                    'last_updated_on',
                    $sessionsTable . '.media_id',
                    $sessionsTable . '.current_second'
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
        $tablePrefix = config('railtracker.table_prefix_media_playback_tracking');
        $typesTable  = $tablePrefix . config('railtracker.media_playback_types', 'media_playback_types');
        $sessionsTable = $tablePrefix . config('railtracker.media_playback_sessions', 'media_playback_sessions');

        return $this->databaseManager->connection(config('railtracker.database_connection_name'))->table($sessionsTable)
            ->leftJoin(
                $typesTable,
                function (JoinClause $join) use ($typesTable, $sessionsTable){
                    return $join->on(
                        $typesTable . '.id',
                        '=',
                        $sessionsTable . '.type_id'
                    );
                }
            )
            ->where('user_id', $userId)
            ->where($sessionsTable . '.media_id', $mediaId)
            ->where($typesTable . '.id', $mediaTypeId)
            ->sum('seconds_played');
    }

    public function getAssignmentTypeIds()
    {
        $tablePrefix = config('railtracker.table_prefix_media_playback_tracking');
        $assignmentTypeIds = $this->databaseManager->connection(config('railtracker.database_connection_name'))
            ->table($tablePrefix . config('railtracker.media_playback_types', 'media_playback_types'))->where('type', '=','assignment')->get('id');

        return $assignmentTypeIds->pluck('id')->toArray();
    }
}