<?php

namespace RTippin\MessengerBots\Bots;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RTippin\Messenger\Actions\Bots\BotActionHandler;
use RTippin\Messenger\Actions\Messages\StoreMessage;
use Throwable;

class YoMommaBot extends BotActionHandler
{
    /**
     * @var StoreMessage
     */
    private StoreMessage $storeMessage;

    /**
     * YoMommaBot constructor.
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
            'alias' => 'yomomma',
            'description' => 'Get a random yo momma joke.',
            'name' => 'YoMomma Bot',
            'unique' => false,
        ];
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
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
