<?php

namespace Railroad\Railtracker\Trackers;

use Carbon\Carbon;
use Railroad\Railtracker\Events\MediaPlaybackTracked;
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

        $id = $this->query(ConfigService::$tableMediaPlaybackSessions)->insertGetId($data);

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
                $data['last_updated_on']
            )
        );

        return $id;
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

        $session = (array)$this->query(ConfigService::$tableMediaPlaybackSessions)
            ->where(['id' => $sessionId])
            ->take(1)
            ->first();

        if (!empty($session)) {
            $return = $this->query(ConfigService::$tableMediaPlaybackSessions)
                ->where(['id' => $sessionId])
                ->take(1)
                ->update($data);

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
                    $data['last_updated_on']
                )
            );
        } else {
            $return = 0;
        }

        return $return;
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