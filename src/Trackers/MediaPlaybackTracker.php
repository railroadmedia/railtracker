<?php

namespace Railroad\Railtracker\Trackers;

use Carbon\Carbon;
use Exception;
use Railroad\Railtracker\Events\MediaPlaybackTracked;
use Railroad\Railtracker\Services\ConfigService;
use Ramsey\Uuid\Uuid;

/*
 * This does NOT implement TrackerInterface because it doesn't use Doctrine ORM, and the writing of media-playback data
 * should happen right away—rather than on the other side of a caching queue-like system—because UX would suffer if
 * progress data not available for use right away.
 *
 * Jonathan, April 2019
 */

class MediaPlaybackTracker extends TrackerBase
{
    /**
     * $startedOn must be a datetime string.
     *
     * @param  string  $mediaId
     * @param  int  $mediaLengthSeconds
     * @param  int  $userId
     * @param  int  $typeId
     * @param  int  $currentSecond
     * @param  int  $secondsPlayed
     * @param  string  $startedOn
     * @return array
     * @throws Exception
     */
    public function trackMediaPlaybackStart(
        $mediaId,
        $mediaLengthSeconds,
        $userId,
        $typeId,
        $currentSecond = 0,
        $secondsPlayed = 0,
        $brand,
        $startedOn = null
    ) {
        if (empty($startedOn)) {
            $startedOn = Carbon::now()->toDateTimeString();
        }

        $data = [
            'uuid' => Uuid::uuid4(),
            'media_id' => $mediaId,
            'media_length_seconds' => $mediaLengthSeconds,
            'user_id' => $userId,
            'type_id' => $typeId,
            'seconds_played' => max($secondsPlayed, 0),
            'current_second' => max($currentSecond, 0),
            'started_on' => $startedOn,
            'last_updated_on' => $startedOn,
        ];

        $sessionsTable = config('railtracker.table_prefix_media_playback_tracking') .
            config('railtracker.media_playback_sessions_table', 'media_playback_sessions');

        $id = $this->query($sessionsTable)->insertGetId($data);

        $data['id'] = $id;

        event(
            new MediaPlaybackTracked(
                $id,
                $data['media_id'],
                $data['media_length_seconds'],
                $data['user_id'],
                $data['type_id'],
                $data['seconds_played'],
                $data['current_second'],
                $data['started_on'],
                $data['last_updated_on'],
                $brand
            )
        );
        $data['brand'] = $brand;
        return $data;
    }

    /**
     * $startedOn must be a datetime string.
     *
     * @param  int  $sessionId
     * @param  int  $secondsPlayed
     * @param  int  $currentSecond
     * @param  null  $lastUpdatedOn
     * @return array|boolean
     */
    public function trackMediaPlaybackProgress(
        $sessionId,
        $secondsPlayed,
        $currentSecond,
        $lastUpdatedOn = null,
        $brand = null
    ) {
        if (empty($lastUpdatedOn)) {
            $lastUpdatedOn = Carbon::now()->toDateTimeString();
        }

        $data = [
            'seconds_played' => $secondsPlayed,
            'current_second' => max($currentSecond, 0),
            'last_updated_on' => $lastUpdatedOn,
        ];

        $sessionsTable = config('railtracker.table_prefix_media_playback_tracking') .
            config('railtracker.media_playback_sessions_table', 'media_playback_sessions');

        $session = (array) $this->query($sessionsTable)
            ->where(['id' => $sessionId])
            ->take(1)
            ->first();

        if (!empty($session)) {
            $updated = $this->query($sessionsTable)
                ->where(['id' => $sessionId])
                ->update($data);

            if (!$updated) {
                return false;
            }

            event(
                new MediaPlaybackTracked(
                    $session['id'],
                    $session['media_id'],
                    $session['media_length_seconds'],
                    $session['user_id'],
                    $session['type_id'],
                    $data['seconds_played'],
                    $data['current_second'],
                    $session['started_on'],
                    $data['last_updated_on'],
                    $brand
                )
            );

            return array_merge($session, $data);
        }

        return false;
    }

    /**
     * @param  string  $type
     * @param  string  $category
     * @return int
     */
    public function trackMediaType($type, $category)
    {
        $data = [
            'type' => substr($type, 0, 128),
            'category' => substr($category, 0, 128),
        ];

        $typesTable = config('railtracker.table_prefix_media_playback_tracking') .
            config('railtracker.media_playback_types_table', 'media_playback_types');

        return $this->storeAndCache($data, $typesTable);
    }
}
