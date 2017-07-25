<?php

namespace Railroad\Railtracker\Trackers;

use Carbon\Carbon;
use Railroad\Railtracker\Services\ConfigService;
use Ramsey\Uuid\Uuid;

class MediaPlaybackTracker extends TrackerBase
{
    /**
     * $startedOn must be a datetime string.
     *
     * @param string $mediaId
     * @param int $mediaLengthSeconds
     * @param int $userId
     * @param int $typeId
     * @param int $currentSecond
     * @param string $startedOn
     * @return int
     */
    public function trackMediaPlaybackStart(
        $mediaId,
        $mediaLengthSeconds,
        $userId,
        $typeId,
        $currentSecond = 0,
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
            'seconds_played' => 0,
            'current_second' => max($currentSecond, 0),
            'started_on' => $startedOn,
            'last_updated_on' => $startedOn,
        ];

        return $this->query(ConfigService::$tableMediaPlaybackSessions)->insertGetId($data);
    }

    /**
     * $startedOn must be a datetime string.
     *
     * @param int $sessionId
     * @param int $secondsPlayed
     * @param int $currentSecond
     * @param null $lastUpdatedOn
     * @return int
     */
    public function trackMediaPlaybackProgress(
        $sessionId,
        $secondsPlayed,
        $currentSecond,
        $lastUpdatedOn = null
    ) {
        if (empty($lastUpdatedOn)) {
            $lastUpdatedOn = Carbon::now()->toDateTimeString();
        }

        $data = [
            'seconds_played' => $secondsPlayed,
            'current_second' => max($currentSecond, 0),
            'last_updated_on' => $lastUpdatedOn,
        ];

        return $this->query(ConfigService::$tableMediaPlaybackSessions)
            ->where(['id' => $sessionId])
            ->take(1)
            ->update($data);
    }

    /**
     * @param string $type
     * @param string $category
     * @return int
     */
    public function trackMediaType($type, $category)
    {
        $data = [
            'type' => substr($type, 0, 128),
            'category' => substr($category, 0, 128),
        ];

        return $this->storeAndCache($data, ConfigService::$tableMediaPlaybackTypes);
    }
}