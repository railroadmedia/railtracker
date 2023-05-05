<?php

use Illuminate\Support\Facades\Route;
use Railroad\Railtracker\Controllers\MediaPlaybackTrackingJsonController;

Route::group(
    [
        'middleware' => config('railtracker.route_middleware_logged_in_groups'),
    ],
    function () {
        Route::put(
            '/railtracker/media-playback-session/store',
            MediaPlaybackTrackingJsonController::class . '@store'
        )
            ->name('railtracker.media-playback-session.store');

        Route::post(
            '/railtracker/media-playback-session',
            MediaPlaybackTrackingJsonController::class . '@store'
        )
            ->name('railtracker.media-playback-session.post');

        Route::patch(
            '/railtracker/media-playback-session/update/{sessionId}',
            MediaPlaybackTrackingJsonController::class . '@update'
        )
            ->name('railtracker.media-playback-session.update');

        Route::get(
            '/railtracker/last-engaged/store',
            \Railroad\Railtracker\Controllers\ContentLastEngagedJsonController::class . '@store'
        )
            ->name('railtracker.last-engaged.store');
    }
);
