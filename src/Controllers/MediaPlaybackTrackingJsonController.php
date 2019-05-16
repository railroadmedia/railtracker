<?php

namespace Railroad\Railtracker\Controllers;

use Exception;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Railroad\Railtracker\Trackers\MediaPlaybackTracker;

class MediaPlaybackTrackingJsonController extends Controller
{
    use ValidatesRequests;

    /**
     * @var MediaPlaybackTracker
     */
    private $mediaPlaybackTracker;

    /**
     * MediaPlaybackTrackingController constructor.
     */
    public function __construct(MediaPlaybackTracker $mediaPlaybackTracker)
    {
        $this->mediaPlaybackTracker = $mediaPlaybackTracker;
    }

    /**
     * @param  Request  $request
     * @return JsonResponse
     * @throws Exception
     */
    public function store(Request $request)
    {
        $userId = auth()->id() ?? null;

        try {
            $this->validate(
                $request,
                [
                    'data.type' => 'in:media-playback-session',
                    'data.attributes.media_id' => 'required',
                    'data.attributes.media_length_seconds' => 'required|numeric',
                    'data.attributes.media_type' => 'required|string',
                    'data.attributes.media_category' => 'required|string',
                    'data.attributes.current_second' => 'numeric',
                    'data.attributes.seconds_played' => 'numeric',
                ]
            );
        } catch (ValidationException $exception) {
            throw new HttpResponseException(
                response()->json(
                    [
                        'errors' => $exception->errors(),
                    ],
                    422
                )
            );
        }

        $mediaTypeId = $this->mediaPlaybackTracker->trackMediaType(
            $request->input('data.attributes.media_type'),
            $request->input('data.attributes.media_category')
        );

        $data = $this->mediaPlaybackTracker->trackMediaPlaybackStart(
            $request->input('data.attributes.media_id'),
            $request->input('data.attributes.media_length_seconds'),
            $userId,
            $mediaTypeId,
            $request->input('data.attributes.current_second', 0),
            $request->input('data.attributes.seconds_played', 0)
        );

        return response()->json([
            'data' => [
                'type' => 'media-playback-session',
                'id' => $data['id'],
                'uuid' => $data['uuid'],
                'media_id' => $data['media_id'],
                'media_length_seconds' => $data['media_length_seconds'],
                'user_id' => $data['user_id'],
                'type_id' => $data['type_id'],
                'current_second' => $data['current_second'],
                'seconds_played' => $data['seconds_played'],
                'started_on' => $data['started_on'],
                'last_updated_on' => $data['last_updated_on'],
            ]
        ], 201);
    }

    /**
     * @param  Request  $request
     * @param $sessionId
     * @return JsonResponse
     */
    public function update(Request $request, $sessionId)
    {
        try {
            $this->validate(
                $request,
                [
                    'data.attributes.seconds_played' => 'required|numeric',
                    'data.attributes.current_second' => 'required|numeric',
                ]
            );

        } catch (ValidationException $exception) {
            throw new HttpResponseException(
                response()->json(
                    [
                        'errors' => $exception->errors(),
                    ],
                    422
                )
            );
        }

        $data = $this->mediaPlaybackTracker->trackMediaPlaybackProgress(
            $sessionId,
            $request->input('data.attributes.seconds_played'),
            $request->input('data.attributes.current_second')
        );

        if (!$data) {
            response()->json([], 404);
        }

        return response()->json([
            'data' => [
                'type' => 'media-playback-session',
                'id' => $data['id'],
                'uuid' => $data['uuid'],
                'media_id' => $data['media_id'],
                'media_length_seconds' => $data['media_length_seconds'],
                'user_id' => $data['user_id'],
                'type_id' => $data['type_id'],
                'current_second' => $data['current_second'],
                'seconds_played' => $data['seconds_played'],
                'started_on' => $data['started_on'],
                'last_updated_on' => $data['last_updated_on'],
            ]
        ], 200);
    }
}
