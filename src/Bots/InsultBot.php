<?php

namespace RTippin\MessengerBots\Bots;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RTippin\Messenger\Actions\Bots\BotActionHandler;
use Throwable;

class InsultBot extends BotActionHandler
{
    /**
     * Endpoint we gather data from.
     */
    const API_ENDPOINT = 'https://evilinsult.com/generate_insult.php?lang=en&type=json';

    /**
     * The bots settings.
     *
     * @return array
     */
    public static function getSettings(): array
    {
        return [
            'alias' => 'insult',
            'description' => 'Responds with a random insult.',
            'name' => 'Insult',
            'unique' => true,
        ];
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        $insult = $this->getInsult();

        if ($insult->failed()) {
            $this->releaseCooldown();

            return;
        }

        $insult = htmlspecialchars_decode($insult->json('insult'));

        $this->composer()->emitTyping()->message("{$this->message->owner->getProviderName()}, $insult");
    }

    /**
     * @return Response
     */
    private function getInsult(): Response
    {
        return Http::acceptJson()->timeout(15)->get(self::API_ENDPOINT);
    }
}
