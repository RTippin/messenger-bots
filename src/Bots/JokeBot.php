<?php

namespace RTippin\MessengerBots\Bots;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RTippin\Messenger\Actions\Bots\BotActionHandler;
use Throwable;

class JokeBot extends BotActionHandler
{
    /**
     * Endpoint we gather data from.
     */
    const API_ENDPOINT = 'https://official-joke-api.appspot.com/jokes/random';

    /**
     * The bots settings.
     *
     * @return array
     */
    public static function getSettings(): array
    {
        return [
            'alias' => 'random_joke',
            'description' => 'Get a random joke. Has a setup and a punchline.',
            'name' => 'Jokester',
            'unique' => true,
        ];
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        $joke = $this->getJoke();

        if ($joke->successful()) {
            $this->composer()->emitTyping()->message($joke->json('setup'));

            if (! self::isTesting()) {
                sleep(6);
            }

            $this->composer()->emitTyping()->message($joke->json('punchline'));

            return;
        }

        $this->releaseCooldown();
    }

    /**
     * @return Response
     */
    private function getJoke(): Response
    {
        return Http::acceptJson()->timeout(15)->get(self::API_ENDPOINT);
    }
}
