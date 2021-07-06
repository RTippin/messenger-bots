<?php

namespace RTippin\MessengerBots\Bots;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RTippin\Messenger\Actions\Bots\BotActionHandler;
use Throwable;

class KanyeBot extends BotActionHandler
{
    /**
     * The bots settings.
     *
     * @return array
     */
    public static function getSettings(): array
    {
        return [
            'alias' => 'kanye',
            'description' => 'Get a random Kanye West quote.',
            'name' => 'Kanye West',
            'unique' => true,
        ];
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        $quote = $this->getKanyeQuote();

        if ($quote->successful()) {
            $this->composer()->emitTyping()->message(":bearded_person_tone5: \"{$quote->json('quote')}\"");

            return;
        }

        $this->releaseCooldown();
    }

    /**
     * @return Response
     */
    private function getKanyeQuote(): Response
    {
        return Http::acceptJson()->timeout(30)->get('https://api.kanye.rest/');
    }
}
