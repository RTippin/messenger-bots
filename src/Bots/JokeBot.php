<?php

namespace RTippin\MessengerBots\Bots;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RTippin\Messenger\Actions\Bots\BotActionHandler;
use RTippin\Messenger\Actions\Messages\StoreMessage;
use Throwable;

class JokeBot extends BotActionHandler
{
    /**
     * @var StoreMessage
     */
    private StoreMessage $storeMessage;

    /**
     * JokeBot constructor.
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
            'alias' => 'random_joke',
            'description' => 'Get a random joke. Has a setup and a punchline.',
            'name' => 'Jokester',
            'unique' => true,
        ];
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        $joke = $this->getJoke();

        if ($joke->successful()) {
            $this->storeMessage->execute($this->thread, [
                'message' => $joke->json('setup'),
            ]);

            sleep(6);

            $this->storeMessage->execute($this->message->thread, [
                'message' => $joke->json('punchline'),
            ]);

            return;
        }

        $this->releaseCooldown();
    }

    /**
     * @return Response
     */
    private function getJoke(): Response
    {
        return Http::acceptJson()->timeout(30)->get('https://official-joke-api.appspot.com/jokes/random');
    }
}
