<?php

namespace RTippin\MessengerBots\Bots;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RTippin\Messenger\Actions\Bots\BotActionHandler;
use RTippin\Messenger\Actions\Messages\StoreMessage;
use RTippin\Messenger\Exceptions\InvalidProviderException;
use RTippin\Messenger\Messenger;
use Throwable;

class YoMommaBot extends BotActionHandler
{
    /**
     * @var string
     */
    public static string $description = 'Get a random yo momma joke.';

    /**
     * @var Messenger
     */
    private Messenger $messenger;

    /**
     * @var StoreMessage
     */
    private StoreMessage $storeMessage;

    /**
     * YoMommaBot constructor.
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

        $joke = $this->getYoMomma();

        if ($joke->successful()) {
            $this->storeMessage->execute($this->message->thread, [
                'message' => ":woman: {$joke->json()['joke']}",
            ]);
        }
    }

    /**
     * @return Response
     */
    private function getYoMomma(): Response
    {
        return Http::acceptJson()->timeout(30)->get('https://api.yomomma.info/');
    }
}
