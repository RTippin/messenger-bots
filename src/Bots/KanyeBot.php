<?php

namespace RTippin\MessengerBots\Bots;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RTippin\Messenger\Actions\Bots\BotActionHandler;
use RTippin\Messenger\Actions\Messages\StoreMessage;
use Throwable;

class KanyeBot extends BotActionHandler
{
    /**
     * @var StoreMessage
     */
    private StoreMessage $storeMessage;

    /**
     * KanyeBot constructor.
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
            'alias' => 'kanye',
            'description' => 'Get a random kanye quote.',
            'name' => 'Kanye',
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
            $this->storeMessage->execute($this->thread, [
                'message' => ":bearded_person_tone5: \"{$quote->json()['quote']}\"",
            ]);

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
