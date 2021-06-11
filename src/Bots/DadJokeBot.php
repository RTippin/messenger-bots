<?php

namespace RTippin\MessengerBots\Bots;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RTippin\Messenger\Actions\Bots\BotActionHandler;
use RTippin\Messenger\Actions\Messages\StoreMessage;
use RTippin\Messenger\Exceptions\InvalidProviderException;
use RTippin\Messenger\Messenger;
use Throwable;

class DadJokeBot extends BotActionHandler
{
    /**
     * Set the alias we will use when attaching the handler to
     * a bot model via a form post.
     *
     * @return string
     */
    public static function getAlias(): string
    {
        return 'dad.joke';
    }

    /**
     * Set the description of the handler.
     *
     * @return string
     */
    public static function getDescription(): string
    {
        return 'Get a random dad joke.';
    }

    /**
     * Set the name of the handler we will display to the frontend.
     *
     * @return string
     */
    public static function getName(): string
    {
        return 'Dad Joke Bot';
    }

    /**
     * @var Messenger
     */
    private Messenger $messenger;

    /**
     * @var StoreMessage
     */
    private StoreMessage $storeMessage;

    /**
     * DadJokeBot constructor.
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

        $joke = $this->getDadJoke();

        if ($joke->successful()) {
            $this->storeMessage->execute($this->message->thread, [
                'message' => ":man: {$joke->json()['joke']}",
            ]);
        }
    }

    /**
     * @return Response
     */
    private function getDadJoke(): Response
    {
        return Http::acceptJson()->timeout(30)->get('https://icanhazdadjoke.com/');
    }
}
