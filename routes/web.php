<?php

use Illuminate\Support\Facades\Route;
use RTippin\MessengerBots\Http\Controllers\RenderBotAvatar;

/*
|--------------------------------------------------------------------------
| Messenger WEB Routes
|--------------------------------------------------------------------------
*/

Route::prefix('threads/{thread}/bots/{bot}')->name('messenger.threads.bots.')->group(function () {
    Route::get('avatar/{size}/{image}', RenderBotAvatar::class)->name('avatar.render');
});
