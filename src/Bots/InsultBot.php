<?php

namespace RTippin\MessengerBots\Bots;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RTippin\Messenger\Actions\Messages\StoreMessage;
use RTippin\Messenger\Contracts\BotHandler;
use RTippin\Messenger\Exceptions\InvalidProviderException;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Action;
use RTippin\Messenger\Models\Message;
use Throwable;

class InsultBot implements BotHandler
{
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
     * @param Action $action
     * @param Message $message
     * @throws InvalidProviderException
     * @throws Throwable
     */
    public function execute(Action $action, Message $message): void
    {
        $this->messenger->setProvider($action->bot);

        $insult = $this->getInsult();

        if ($insult->successful()) {
            $insult = htmlspecialchars_decode($insult->json()['insult']);

            $this->storeMessage->execute($message->thread, [
                'message' => "{$message->owner->getProviderName()}, $insult",
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
