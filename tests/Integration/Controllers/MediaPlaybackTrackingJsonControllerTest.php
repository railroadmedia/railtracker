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

        $response->assertJsonValidationErrors([
            'data.attributes.media_id',
            'data.attributes.media_length_seconds',
            'data.attributes.media_type',
            'data.attributes.media_category',
        ]);

        $this->assertEquals(422, $response->getStatusCode());
    }

    public function test_store_validation_extras()
    {
        $response = $this->call('put', '/railtracker/media-playback-session/store', [
            'data' => [
                'attributes' => [
                    'current_second' => 'non-int',
                    'seconds_played' => 'non-int',
                ]
            ]
        ]);

        $response->assertJsonValidationErrors([
            'data.attributes.media_id',
            'data.attributes.media_length_seconds',
            'data.attributes.media_type',
            'data.attributes.media_category',
            'data.attributes.current_second',
            'data.attributes.seconds_played',
        ]);

        $this->assertEquals(422, $response->getStatusCode());
    }

    public function test_store_only_required()
    {
        $attributes = [
            'media_id' => $this->faker->word.rand(),
            'media_length_seconds' => rand(),
            'media_type' => $this->faker->word,
            'media_category' => $this->faker->word,
        ];

        $response = $this->call('put', '/railtracker/media-playback-session/store', [
            'data' => [
                'attributes' => $attributes
            ]
        ]);

        $this->assertArraySubset([
            'data' => [
                'type' => 'media-playback-session',
                'id' => 1,
                'media_id' => $attributes['media_id'],
                'media_length_seconds' => $attributes['media_length_seconds'],
                'user_id' => null,
                'type_id' => 1,
                'current_second' => 0,
                'seconds_played' => 0,
                'started_on' => Carbon::now()->toDateTimeString(),
                'last_updated_on' => Carbon::now()->toDateTimeString()
            ]
        ], $response->json());

        $this->assertEquals(201, $response->getStatusCode());
    }

    public function test_update_validation()
    {
        $userId = $this->createAndLogInNewUser();

        $attributes = [
            'media_id' => $this->faker->word.rand(),
            'media_length_seconds' => rand(),
            'media_type' => $this->faker->word,
            'media_category' => $this->faker->word,
            'current_second' => rand(),
            'seconds_played' => rand(),
        ];

        $response = $this->call('put', '/railtracker/media-playback-session/store', [
            'data' => [
                'attributes' => $attributes
            ]
        ]);

        $sessionId = $response->json()['data']['id'];
        unset($attributes['current_second']);
        unset($attributes['seconds_played']);

        $response = $this->call('patch', '/railtracker/media-playback-session/update/'.$sessionId, [
            'data' => [
                'id' => $sessionId,
                'attributes' => $attributes
            ]
        ]);

        $response->assertJsonValidationErrors([
            'data.attributes.current_second',
            'data.attributes.seconds_played',
        ]);
    }

    public function test_update_all()
    {
        $userId = $this->createAndLogInNewUser();

        $attributes = [
            'media_id' => $this->faker->word.rand(),
            'media_length_seconds' => rand(),
            'media_type' => $this->faker->word,
            'media_category' => $this->faker->word,
            'current_second' => rand(),
            'seconds_played' => rand(),
        ];

        $response = $this->call('put', '/railtracker/media-playback-session/store', [
            'data' => [
                'attributes' => $attributes
            ]
        ]);

        $sessionId = $response->json()['data']['id'];
        $attributes['current_second'] = rand();
        $attributes['seconds_played'] = rand();

        $response = $this->call('patch', '/railtracker/media-playback-session/update/'.$sessionId, [
            'data' => [
                'id' => $sessionId,
                'attributes' => $attributes
            ]
        ]);

        $this->assertArraySubset([
            'data' => [
                'type' => 'media-playback-session',
                'id' => $sessionId,
                'media_id' => $attributes['media_id'],
                'media_length_seconds' => $attributes['media_length_seconds'],
                'user_id' => $userId,
                'type_id' => 1,
                'current_second' => $attributes['current_second'],
                'seconds_played' => $attributes['seconds_played'],
                'started_on' => Carbon::now()->toDateTimeString(),
                'last_updated_on' => Carbon::now()->toDateTimeString()
            ]
        ], $response->json());
    }
}