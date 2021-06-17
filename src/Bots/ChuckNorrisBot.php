<?php

namespace RTippin\MessengerBots\Bots;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RTippin\Messenger\Actions\Bots\BotActionHandler;
use RTippin\Messenger\Actions\Messages\StoreMessage;
use Throwable;

class ChuckNorrisBot extends BotActionHandler
{
    /**
     * @var StoreMessage
     */
    private StoreMessage $storeMessage;

    /**
     * ChuckNorrisBot constructor.
     *
     * @param StoreMessage $storeMessage
     */
    public function __construct(StoreMessage $storeMessage)
    {
        $this->storeMessage = $storeMessage;
    }

    /**
     * The bots settings.
     *
     * @return array
     */
    public static function getSettings(): array
    {
        return [
            'alias' => 'chuck',
            'description' => 'Get a random chuck norris joke.',
            'name' => 'Chuck Norris Bot',
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
            $this->storeMessage->execute($this->thread, [
                'message' => ":skull: {$quote->json()['value']}",
            ]);

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
