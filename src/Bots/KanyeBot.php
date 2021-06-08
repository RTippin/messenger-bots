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

class KanyeBot implements BotHandler
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
     * KanyeBot constructor.
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
     * @param string $matchingTrigger
     * @throws InvalidProviderException
     * @throws Throwable
     */
    public function execute(Action $action, Message $message, string $matchingTrigger): void
    {
        $this->messenger->setProvider($action->bot);

        $quote = $this->getKanyeQuote();

        if ($quote->successful()) {
            $this->storeMessage->execute($message->thread, [
                'message' => ":bearded_person_tone5: \"{$quote->json()['quote']}\"",
            ]);
        }
    }

    /**
     * @return Response
     */
    private function getKanyeQuote(): Response
    {
        return Http::acceptJson()->timeout(30)->get('https://api.kanye.rest/');
    }
}
