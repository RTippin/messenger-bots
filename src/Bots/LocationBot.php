<?php

namespace RTippin\MessengerBots\Bots;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RTippin\Messenger\Actions\Bots\BotActionHandler;
use RTippin\Messenger\MessengerBots;
use Throwable;

class LocationBot extends BotActionHandler
{
    /**
     * Free endpoint for IP-API.
     */
    const API_ENDPOINT_FREE = 'http://ip-api.com/json/';

    /**
     * Pro endpoint for IP-API.
     */
    const API_ENDPOINT_PRO = 'https://pro.ip-api.com/json/';

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
            'triggers' => ['!location', '!findMe', '!whereAmI'],
            'match' => MessengerBots::MATCH_EXACT_CASELESS,
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

        $this->sendFailedMessage();

        $this->releaseCooldown();
    }

    /**
     * @param  array  $location
     *
     * @throws Throwable
     */
    private function sendLocationMessage(array $location): void
    {
        $this->composer()->emitTyping()->message(
            "My sources say you are coming all the way from {$location['city']}, {$location['regionName']}, {$location['country']}!",
            $this->message->id
        );
    }

    /**
     * @throws Throwable
     */
    private function sendFailedMessage(): void
    {
        $this->composer()->emitTyping()->message(
            'It seems that I have no clue where you are right now!',
            $this->message->id
        );
    }

    /**
     * @return Response
     */
    private function getLocation(): Response
    {
        $apiKey = config('messenger-bots.ip_api_key');
        $endpoint = $apiKey ? self::API_ENDPOINT_PRO : self::API_ENDPOINT_FREE;

        return Http::timeout(15)->get($endpoint.$this->senderIp, [
            'key' => $apiKey,
            'fields' => 'status,country,regionName,city',
        ]);
    }
}
