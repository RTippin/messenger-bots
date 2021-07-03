<?php

namespace RTippin\MessengerBots\Bots;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RTippin\Messenger\Actions\Bots\BotActionHandler;
use Throwable;

class DadJokeBot extends BotActionHandler
{
    /**
     * The bots settings.
     *
     * @return array
     */
    public static function getSettings(): array
    {
        return [
            'alias' => 'dad_joke',
            'description' => 'Get a random dad joke.',
            'name' => 'Dad Joke',
            'unique' => true,
        ];
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        $joke = $this->getDadJoke();

        if ($joke->successful()) {
            $this->composer()->message(":man: {$joke->json('joke')}");

            return;
        }

        $this->releaseCooldown();
    }

    /**
     * @return Response
     */
    private function getDadJoke(): Response
    {
        return Http::acceptJson()->timeout(30)->get('https://icanhazdadjoke.com/');
    }
}
