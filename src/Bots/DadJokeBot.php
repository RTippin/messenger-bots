<?php

namespace RTippin\MessengerBots\Bots;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RTippin\Messenger\Actions\Bots\BotActionHandler;
use RTippin\Messenger\Actions\Messages\StoreMessage;
use Throwable;

class DadJokeBot extends BotActionHandler
{
    /**
     * @var StoreMessage
     */
    private StoreMessage $storeMessage;

    /**
     * DadJokeBot constructor.
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
            'alias' => 'dad.joke',
            'description' => 'Get a random dad joke.',
            'name' => 'Dad Joke Bot',
        ];
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
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
