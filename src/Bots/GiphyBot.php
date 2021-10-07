<?php

namespace RTippin\MessengerBots\Bots;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RTippin\Messenger\Actions\Bots\BotActionHandler;
use Throwable;

class GiphyBot extends BotActionHandler
{
    /**
     * Endpoint we gather data from.
     */
    const API_ENDPOINT = 'https://api.giphy.com/v1/gifs/random';

    /**
     * The bots settings.
     *
     * @return array
     */
    public static function getSettings(): array
    {
        return [
            'alias' => 'giphy',
            'description' => 'Get a random gif from giphy, with an optional tag. [ !gif {tag?} ]',
            'name' => 'Giphy',
            'unique' => true,
            'triggers' => ['!gif', '!giphy'],
            'match' => 'starts:with:caseless',
        ];
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        $gif = $this->getGif();

        if ($gif->failed()) {
            $this->releaseCooldown();

            return;
        }

        $this->composer()->emitTyping()->message($gif->json('data')['image_url']);
    }

    /**
     * @return Response
     */
    private function getGif(): Response
    {
        return Http::acceptJson()->timeout(15)->get(self::API_ENDPOINT, [
            'api_key' => config('messenger-bots.giphy_api_key'),
            'tag' => $this->getParsedMessage(),
        ]);
    }
}
