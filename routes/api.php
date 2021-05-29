<?php

use Illuminate\Support\Facades\Route;
use RTippin\MessengerBots\Http\Controllers\BotController;
use RTippin\MessengerBots\Http\Controllers\RenderBotAvatar;

/*
|--------------------------------------------------------------------------
| Messenger Bots API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('threads/{thread}')->name('api.messenger.threads.')->group(function () {
    Route::apiResource('bots', BotController::class);
    Route::get('bots/{bot}/avatar/{size}/{image}', RenderBotAvatar::class)->name('bots.avatar.render');
});
