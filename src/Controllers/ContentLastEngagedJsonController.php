<?php

namespace Railroad\Railtracker\Controllers;

use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Railroad\Railtracker\Services\ContentLastEngagedService;

class ContentLastEngagedJsonController extends Controller
{
    use ValidatesRequests;

    private ContentLastEngagedService $contentLastEngagedService;

    /**
     * @param ContentLastEngagedService $contentLastEngagedService
     */
    public function __construct(
        ContentLastEngagedService $contentLastEngagedService
    ) {
        $this->contentLastEngagedService = $contentLastEngagedService;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $this->validate($request, [
                'content_id' => 'required|numeric',
                'parent_playlist_id' => 'integer',
                'parent_content_id' => 'integer',
            ]);
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

        $lastEngagedContent = $this->contentLastEngagedService->engageContent(
            auth()->id(),
            $request->input('content_id'),
            $request->input('parent_playlist_id'),
            $request->input('parent_content_id')
        );

        return response()->json(
            $lastEngagedContent,
            201
        );
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function delete(Request $request)
    {
        try {
            $this->validate($request, [
                'content_id' => 'required|numeric',
                'parent_playlist_id' => 'integer',
                'parent_content_id' => 'integer',
            ]);
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

        $this->contentLastEngagedService->deleteEngagedContent(
            auth()->id(),
            $request->input('content_id'),
            $request->input('parent_playlist_id'),
            $request->input('parent_content_id')
        );

        return response()->json(
            null,
            204
        );
    }
}
