<?php

use Ddt\JobBot\Http\Controllers\JobBotController;
use Illuminate\Support\Facades\Route;

$prefix     = config('jobbot.route_prefix', 'jobbot');
$middleware = config('jobbot.middleware', ['web', 'auth']);

Route::prefix($prefix)
    ->middleware($middleware)
    ->name('jobbot.')
    ->group(function () {
        Route::post('/query',  [JobBotController::class, 'query'])->name('query');
        Route::post('/resume', [JobBotController::class, 'uploadResume'])->name('resume');
    });
