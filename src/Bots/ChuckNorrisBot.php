<?php

namespace RTippin\MessengerBots\Bots;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RTippin\Messenger\Actions\Messages\StoreMessage;
use RTippin\Messenger\Contracts\BotHandler;
use RTippin\Messenger\Exceptions\InvalidProviderException;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\BotAction;
use RTippin\Messenger\Models\Message;
use Throwable;

class ChuckNorrisBot implements BotHandler
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
     * ChuckNorrisBot constructor.
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
     * @param BotAction $action
     * @param Message $message
     * @param string $matchingTrigger
     * @throws InvalidProviderException
     * @throws Throwable
     */
    public function execute(BotAction $action, Message $message, string $matchingTrigger): void
    {
        $this->messenger->setProvider($action->bot);

        $quote = $this->getChuckNorris();

        if ($quote->successful()) {
            $this->storeMessage->execute($message->thread, [
                'message' => ":skull: {$quote->json()['value']}",
            ]);
        }
    }

    /**
     * @return Response
     */
    private function getChuckNorris(): Response
    {
        return Http::acceptJson()->timeout(30)->get('https://api.chucknorris.io/jokes/random');
    }
}
