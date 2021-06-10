<?php

namespace RTippin\MessengerBots\Bots;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RTippin\Messenger\Actions\Bots\BotActionHandler;
use RTippin\Messenger\Actions\Messages\StoreMessage;
use RTippin\Messenger\Exceptions\InvalidProviderException;
use RTippin\Messenger\Messenger;
use Throwable;

class InsultBot extends BotActionHandler
{
    /**
     * @var string
     */
    public static string $description = 'Respond with a random insult.';

    /**
     * @var Messenger
     */
    private Messenger $messenger;

    /**
     * @var StoreMessage
     */
    private StoreMessage $storeMessage;

    /**
     * InsultBot constructor.
     *
     * @param Messenger $messenger
     * @param StoreMessage $storeMessage
     */
    public function __construct(Messenger $messenger, StoreMessage $storeMessage)
    {
        $this->messenger = $messenger;
        $this->storeMessage = $storeMessage;
    }

    /**
     * @throws InvalidProviderException
     * @throws Throwable
     */
    public function handle(): void
    {
        $this->messenger->setProvider($this->action->bot);

        $insult = $this->getInsult();

        if ($insult->successful()) {
            $insult = htmlspecialchars_decode($insult->json()['insult']);

            $this->storeMessage->execute($this->message->thread, [
                'message' => "{$this->message->owner->getProviderName()}, $insult",
            ]);
        }
    }

    /**
     * @return Response
     */
    private function getInsult(): Response
    {
        return Http::acceptJson()->timeout(30)->get('https://evilinsult.com/generate_insult.php?lang=en&type=json');
    }
}
