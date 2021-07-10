<?php

namespace RTippin\MessengerBots\Bots;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RTippin\Messenger\Actions\Bots\BotActionHandler;
use Throwable;

class YoMommaBot extends BotActionHandler
{
    /**
     * Endpoint we gather data from.
     */
    const API_ENDPOINT = 'https://api.yomomma.info/';

    /**
     * The bots settings.
     *
     * @return array
     */
    public static function getSettings(): array
    {
        return [
            'alias' => 'yomomma',
            'description' => 'Get a random yo momma joke.',
            'name' => 'Yo Momma',
            'unique' => true,
        ];
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        $joke = $this->getYoMomma();

        if ($joke->successful()) {
            $this->composer()->emitTyping()->message(":woman: {$joke->json('joke')}");

            return;
        }

        $this->releaseCooldown();
    }

    /**
     * @return Response
     */
    private function getYoMomma(): Response
    {
        return Http::acceptJson()->timeout(15)->get(self::API_ENDPOINT);
    }
}
