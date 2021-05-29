<?php

namespace RTippin\MessengerBots\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Thread;
use RTippin\MessengerBots\Models\Bot;

class BotController
{
    use AuthorizesRequests;

    /**
     * Display a listing of thread bots.
     *
     */
    public function index(Thread $thread)
    {
        return $thread->bots;
    }

    /**
     * Store a bot.
     *
     */
    public function store(Request $request, Thread $thread)
    {
        return $thread->bots()->create([
            'owner_id' => Messenger::getProvider()->getKey(),
            'owner_type' => Messenger::getProvider()->getMorphClass(),
            'name' => $request->input('name')
        ]);
    }

    /**
     * Display the bot.
     */
    public function show(Thread $thread, Bot $bot)
    {
        return $bot;
    }

    /**
     * Update the .
     *
     */
    public function update()
    {
        //
    }

    /**
     * Remove the .
     *
     */
    public function destroy()
    {
        //
    }
}
