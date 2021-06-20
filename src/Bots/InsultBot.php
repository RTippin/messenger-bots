<?php

namespace RTippin\MessengerBots\Bots;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RTippin\Messenger\Actions\Bots\BotActionHandler;
use RTippin\Messenger\Actions\Messages\StoreMessage;
use Throwable;

class InsultBot extends BotActionHandler
{
    /**
     * @var StoreMessage
     */
    private StoreMessage $storeMessage;

    /**
     * InsultBot constructor.
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

        if ($insult->successful()) {
            $insult = htmlspecialchars_decode($insult->json('insult'));

            $this->storeMessage->execute($this->thread, [
                'message' => "{$this->message->owner->getProviderName()}, $insult",
            ]);

            return;
        }

        $this->releaseCooldown();
    }

    /**
     * @return Response
     */
    private function getInsult(): Response
    {
        return Http::acceptJson()->timeout(30)->get('https://evilinsult.com/generate_insult.php?lang=en&type=json');
    }
}
