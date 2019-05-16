<?php

use Illuminate\Support\Facades\Route;
use Railroad\Railtracker\Controllers\MediaPlaybackTrackingJsonController;

Route::put(
    '/railtracker/media-playback-session/store',
    MediaPlaybackTrackingJsonController::class.'@store'
)->name('railtracker.media-playback-session.store');

Route::patch(
    '/railtracker/media-playback-session/update/{sessionId}',
    MediaPlaybackTrackingJsonController::class.'@update'
)->name('railtracker.media-playback-session.update');
