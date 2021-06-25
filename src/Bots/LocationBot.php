<?php

namespace RTippin\MessengerBots\Bots;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RTippin\Messenger\Actions\Bots\BotActionHandler;
use RTippin\Messenger\Actions\Messages\StoreMessage;
use Throwable;

class LocationBot extends BotActionHandler
{
    /**
     * Free endpoint for IP-API
     */
    const Free = 'http://ip-api.com/json/';

    /**
     * Pro endpoint for IP-API
     */
    const Pro = 'https://pro.ip-api.com/json/';

    /**
     * The fields we want in our results.
     */
    const Fields = '?fields=status,country,regionName,city';

    /**
     * @var StoreMessage
     */
    private StoreMessage $storeMessage;

    /**
     * LocationBot constructor.
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
            'alias' => 'location',
            'description' => 'Get the general location of the message sender.',
            'name' => 'Locator',
            'unique' => true,
            'match' => 'exact:caseless',
            'triggers' => ['!location', '!findMe', '!whereAmI'],
        ];
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        $location = $this->getLocation();

        if ($location->successful()
            && $location->json('status') === 'success') {
            $this->sendLocationMessage($location->json());

            return;
        }

        $this->sendFailedMessage();;

        $this->releaseCooldown();
    }

    /**
     * @param array $location
     * @throws Throwable
     */
    private function sendLocationMessage(array $location): void
    {
        $this->storeMessage->execute($this->thread, [
            'message' => "My sources say you are coming all the way from {$location['city']}, {$location['regionName']}, {$location['country']}!",
            'reply_to_id' => $this->message->id,
        ]);
    }

    /**
     * @throws Throwable
     */
    private function sendFailedMessage(): void
    {
        $this->storeMessage->execute($this->thread, [
            'message' => 'It seems that I have no clue where you are right now!',
            'reply_to_id' => $this->message->id,
        ]);
    }

    /**
     * @return Response
     */
    private function getLocation(): Response
    {
        $apiKey = config('messenger-bots.ip_api_key');
        $baseUri = self::Free;
        $keyParam = '';

        if (! is_null($apiKey)) {
            $baseUri = self::Pro;
            $keyParam = '&key='.$apiKey;
        }

        return Http::timeout(30)->get($baseUri.$this->senderIp.self::Fields.$keyParam);
    }
}
