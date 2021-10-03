<?php

namespace RTippin\MessengerBots\Bots;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RTippin\Messenger\Actions\Bots\BotActionHandler;
use Throwable;

class ChuckNorrisBot extends BotActionHandler
{
    /**
     * Endpoint we gather data from.
     */
    const API_ENDPOINT = 'https://api.chucknorris.io/jokes/random';

    /**
     * The bots settings.
     *
     * @return array
     */
    public static function getSettings(): array
    {
        return [
            'alias' => 'chuck',
            'description' => 'Get a random Chuck Norris joke.',
            'name' => 'Chuck Norris',
            'unique' => true,
        ];
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        $quote = $this->getChuckNorris();

        if ($quote->failed()) {
            $this->releaseCooldown();

            return;
        }

        $this->composer()->emitTyping()->message(":skull: {$quote->json('value')}");
    }

    /**
     * @return Response
     */
    private function getChuckNorris(): Response
    {
        return Http::acceptJson()->timeout(15)->get(self::API_ENDPOINT);
    }
}
