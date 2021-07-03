<?php

namespace RTippin\MessengerBots\Bots;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RTippin\Messenger\Actions\Bots\BotActionHandler;
use Throwable;

class ChuckNorrisBot extends BotActionHandler
{
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

        if ($quote->successful()) {
            $this->composer()->message(":skull: {$quote->json('value')}");

            return;
        }

        $this->releaseCooldown();
    }

    /**
     * @return Response
     */
    private function getChuckNorris(): Response
    {
        return Http::acceptJson()->timeout(30)->get('https://api.chucknorris.io/jokes/random');
    }
}
