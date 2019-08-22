<?php

namespace Railroad\Railtracker\Tests\Functional\Trackers;

use Carbon\Carbon;
use Railroad\Railtracker\Events\MediaPlaybackTracked;
use Railroad\Railtracker\Services\ConfigService;
use Railroad\Railtracker\Tests\RailtrackerTestCase;
use Railroad\Railtracker\Trackers\MediaPlaybackTracker;

class MediaPlaybackTrackerTest extends RailtrackerTestCase
{
    /**
     * @var MediaPlaybackTracker
     */
    protected $mediaPlaybackTracker;

    public function setUp()
    {
        parent::setUp();

        $this->mediaPlaybackTracker = $this->app->make(MediaPlaybackTracker::class);
    }

    public function test_track_media_playback_type()
    {
        $type = $this->faker->word;
        $category = $this->faker->word;

        $this->mediaPlaybackTracker->trackMediaType($type, $category);

        $this->assertDatabaseHas(
            config('railtracker.tables.media_playback_types'),
            [
                'type' => $type,
                'category' => $category,
            ]
        );
    }

    public function test_track_media_playback_session_start()
    {
        $userId = $this->createAndLogInNewUser();

        $mediaId = $this->faker->word . rand();
        $mediaLength = rand();
        $mediaType = $this->faker->word;
        $mediaCategory = $this->faker->word;
        $currentSecond = rand();
        $secondsPlayed = rand();

        $mediaTypeId = $this->mediaPlaybackTracker->trackMediaType($mediaType, $mediaCategory);

        $this->expectsEvents([MediaPlaybackTracked::class]);

        $this->mediaPlaybackTracker->trackMediaPlaybackStart(
            $mediaId,
            $mediaLength,
            $userId,
            $mediaTypeId,
            $currentSecond,
            $secondsPlayed
        );

        $this->assertDatabaseHas(
            config('railtracker.tables.media_playback_sessions'),
            [
                'media_id' => $mediaId,
                'media_length_seconds' => $mediaLength,
                'user_id' => $userId,
                'type_id' => $mediaTypeId,
                'current_second' => $currentSecond,
                'seconds_played' => $secondsPlayed,
                'started_on' => Carbon::now()->toDateTimeString(),
                'last_updated_on' => Carbon::now()->toDateTimeString(),
            ]
        );
    }

    public function test_track_media_playback_session_start_no_user()
    {
        $mediaId = $this->faker->word . rand();
        $mediaLength = rand();
        $mediaType = $this->faker->word;
        $mediaCategory = $this->faker->word;

        $mediaTypeId = $this->mediaPlaybackTracker->trackMediaType($mediaType, $mediaCategory);

        $this->expectsEvents([MediaPlaybackTracked::class]);

        $this->mediaPlaybackTracker->trackMediaPlaybackStart(
            $mediaId,
            $mediaLength,
            null,
            $mediaTypeId
        );

        $this->assertDatabaseHas(
            config('railtracker.tables.media_playback_sessions'),
            [
                'media_id' => $mediaId,
                'media_length_seconds' => $mediaLength,
                'user_id' => null,
                'type_id' => $mediaTypeId,
                'seconds_played' => 0,
                'current_second' => 0,
                'started_on' => Carbon::now()->toDateTimeString(),
                'last_updated_on' => Carbon::now()->toDateTimeString(),
            ]
        );
    }

    public function test_track_media_playback_session_progress()
    {
        $userId = $this->createAndLogInNewUser();

        $mediaId = $this->faker->word . rand();
        $mediaLength = rand();
        $mediaType = $this->faker->word;
        $mediaCategory = $this->faker->word;

        $mediaTypeId = $this->mediaPlaybackTracker->trackMediaType($mediaType, $mediaCategory);

        $sessionId = $this->mediaPlaybackTracker->trackMediaPlaybackStart(
            $mediaId,
            $mediaLength,
            $userId,
            $mediaTypeId
        )['id'];

        $secondsPlayed = rand();
        $currentSecond = rand();

        $this->expectsEvents([MediaPlaybackTracked::class]);

        $updated = $this->mediaPlaybackTracker->trackMediaPlaybackProgress(
            $sessionId,
            $secondsPlayed,
            $currentSecond
        );

        $this->assertDatabaseHas(
            config('railtracker.tables.media_playback_sessions'),
            [
                'id' => $sessionId,
                'seconds_played' => $secondsPlayed,
                'current_second' => $currentSecond,
                'last_updated_on' => Carbon::now()->toDateTimeString(),
            ]
        );
    }

    public function test_track_media_playback_session_progress_stress()
    {
        $userId = $this->createAndLogInNewUser();

        $mediaId = $this->faker->word . rand();
        $mediaLength = rand();
        $mediaType = $this->faker->word;
        $mediaCategory = $this->faker->word;

        $mediaTypeId = $this->mediaPlaybackTracker->trackMediaType($mediaType, $mediaCategory);

        $sessionId = $this->mediaPlaybackTracker->trackMediaPlaybackStart(
            $mediaId,
            $mediaLength,
            $userId,
            $mediaTypeId
        )['id'];

        $secondsPlayed = rand();
        $currentSecond = rand();

        for ($i = 0; $i < 25; $i++) {
            $this->mediaPlaybackTracker->trackMediaPlaybackProgress(
                $sessionId,
                rand(),
                rand()
            );
        }

        $updated = $this->mediaPlaybackTracker->trackMediaPlaybackProgress(
            $sessionId,
            $secondsPlayed,
            $currentSecond
        );

        $this->assertDatabaseHas(
            config('railtracker.tables.media_playback_sessions'),
            [
                'id' => $sessionId,
                'seconds_played' => $secondsPlayed,
                'current_second' => $currentSecond,
                'last_updated_on' => Carbon::now()->toDateTimeString(),
            ]
        );
    }

    public function test_track_media_playback_sessions_stress()
    {
        for ($c = 0; $c < 15; $c++) {
            $userId = $this->createAndLogInNewUser();

            $mediaId = $this->faker->word . rand();
            $mediaLength = rand();
            $mediaType = $this->faker->word;
            $mediaCategory = $this->faker->word;

            $mediaTypeId = $this->mediaPlaybackTracker->trackMediaType($mediaType, $mediaCategory);

            $sessionId = $this->mediaPlaybackTracker->trackMediaPlaybackStart(
                $mediaId,
                $mediaLength,
                $userId,
                $mediaTypeId
            )['id'];

            $secondsPlayed = rand();
            $currentSecond = rand();

            for ($i = 0; $i < 5; $i++) {
                $this->mediaPlaybackTracker->trackMediaPlaybackProgress(
                    $sessionId,
                    rand(),
                    rand()
                );
            }

            $updated = $this->mediaPlaybackTracker->trackMediaPlaybackProgress(
                $sessionId,
                $secondsPlayed,
                $currentSecond
            );

            $this->assertDatabaseHas(
                config('railtracker.tables.media_playback_sessions'),
                [
                    'media_id' => $mediaId,
                    'media_length_seconds' => $mediaLength,
                    'user_id' => $userId,
                    'type_id' => $mediaTypeId,
                    'seconds_played' => $secondsPlayed,
                    'current_second' => $currentSecond,
                    'started_on' => Carbon::now()->toDateTimeString(),
                    'last_updated_on' => Carbon::now()->toDateTimeString(),
                ]
            );
        }
    }
}