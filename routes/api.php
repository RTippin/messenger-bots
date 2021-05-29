<?php

use Illuminate\Support\Facades\Route;
use RTippin\MessengerBots\Http\Controllers\BotController;

/*
|--------------------------------------------------------------------------
| Messenger Bots API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('threads/{thread}')->name('api.messenger.threads.')->group(function () {
    Route::apiResource('bots', BotController::class);
});
