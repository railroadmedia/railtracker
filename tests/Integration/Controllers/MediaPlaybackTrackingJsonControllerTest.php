<?php

namespace Railroad\Railtracker\Tests\Integration\Repositories;

use Carbon\Carbon;
use Railroad\Railtracker\Repositories\MediaPlaybackRepository;
use Railroad\Railtracker\Tests\RailtrackerTestCase;
use Railroad\Railtracker\Trackers\MediaPlaybackTracker;

class MediaPlaybackTrackingJsonControllerTest extends RailtrackerTestCase
{
    /**
     * @var MediaPlaybackRepository
     */
    private $mediaPlaybackRepository;

    /**
     * @var MediaPlaybackTracker
     */
    private $mediaPlaybackTracker;

    protected function setUp()
    {
        parent::setUp();

        $this->mediaPlaybackRepository = app(MediaPlaybackRepository::class);
        $this->mediaPlaybackTracker = app(MediaPlaybackTracker::class);
    }

    public function test_store_validation()
    {
        $response = $this->call('put', '/railtracker/media-playback-session/store', []);

        $response->assertJsonValidationErrors(
            [
                'media_id',
                'media_length_seconds',
                'media_type',
                'media_category',
            ]
        );

        $this->assertEquals(422, $response->getStatusCode());
    }

    public function test_store_validation_extras()
    {
        $response = $this->call(
            'put',
            '/railtracker/media-playback-session/store',
            [
                'current_second' => 'non-int',
                'seconds_played' => 'non-int',
            ]
        );

        $response->assertJsonValidationErrors(
            [
                'media_id',
                'media_length_seconds',
                'media_type',
                'media_category',
                'current_second',
                'seconds_played',
            ]
        );

        $this->assertEquals(422, $response->getStatusCode());
    }

    public function test_store_only_required()
    {
        $attributes = [
            'media_id' => $this->faker->word . rand(),
            'media_length_seconds' => rand(),
            'media_type' => $this->faker->word,
            'media_category' => $this->faker->word,
        ];

        $response = $this->call(
            'put',
            '/railtracker/media-playback-session/store',
            $attributes
        );

        $this->assertArraySubset(
            [
                'type' => 'media-playback-session',
                'id' => 1,
                'media_id' => $attributes['media_id'],
                'media_length_seconds' => $attributes['media_length_seconds'],
                'user_id' => null,
                'type_id' => 1,
                'current_second' => 0,
                'seconds_played' => 0,
                'started_on' => Carbon::now()
                    ->toDateTimeString(),
                'last_updated_on' => Carbon::now()
                    ->toDateTimeString()
            ],
            $response->json()
        );

        $this->assertEquals(201, $response->getStatusCode());
    }

    public function test_update_validation()
    {
        $userId = $this->createAndLogInNewUser();

        $attributes = [
            'media_id' => $this->faker->word . rand(),
            'media_length_seconds' => rand(),
            'media_type' => $this->faker->word,
            'media_category' => $this->faker->word,
            'current_second' => rand(),
            'seconds_played' => rand(),
        ];

        $response = $this->call(
            'put',
            '/railtracker/media-playback-session/store',
            $attributes
        );

        $sessionId = $response->json()['id'];
        unset($attributes['current_second']);
        unset($attributes['seconds_played']);

        $response = $this->call(
            'patch',
            '/railtracker/media-playback-session/update/' . $sessionId,
            $attributes
        );

        $response->assertJsonValidationErrors(
            [
                'current_second',
                'seconds_played',
            ]
        );
    }

    public function test_update_all()
    {
        $userId = $this->createAndLogInNewUser();

        $attributes = [
            'media_id' => $this->faker->word . rand(),
            'media_length_seconds' => rand(),
            'media_type' => $this->faker->word,
            'media_category' => $this->faker->word,
            'current_second' => rand(),
            'seconds_played' => rand(),
        ];

        $response = $this->call(
            'put',
            '/railtracker/media-playback-session/store',
            $attributes
        );

        $sessionId = $response->json()['id'];
        $attributes['current_second'] = rand();
        $attributes['seconds_played'] = rand();

        $response = $this->call(
            'patch',
            '/railtracker/media-playback-session/update/' . $sessionId,
            $attributes
        );

        $this->assertArraySubset(
            [
                'type' => 'media-playback-session',
                'id' => $sessionId,
                'media_id' => $attributes['media_id'],
                'media_length_seconds' => $attributes['media_length_seconds'],
                'user_id' => $userId,
                'type_id' => 1,
                'current_second' => $attributes['current_second'],
                'seconds_played' => $attributes['seconds_played'],
                'started_on' => Carbon::now()
                    ->toDateTimeString(),
                'last_updated_on' => Carbon::now()
                    ->toDateTimeString()
            ],
            $response->json()
        );
    }
}