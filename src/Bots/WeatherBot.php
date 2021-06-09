<?php

namespace RTippin\MessengerBots\Bots;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RTippin\Messenger\Actions\Messages\StoreMessage;
use RTippin\Messenger\Contracts\BotHandler;
use RTippin\Messenger\Exceptions\InvalidProviderException;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\BotAction;
use RTippin\Messenger\Models\Message;
use Throwable;

class WeatherBot implements BotHandler
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
     * WeatherBot constructor.
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

        $weather = $this->getWeather($matchingTrigger, $message->body);

        if ($weather->successful()) {
            $this->storeMessage->execute($message->thread, [
                'message' => $this->generateWeatherText($weather->json()),
            ]);
        }
    }

    /**
     * @param string $trigger
     * @param string $body
     * @return Response
     */
    private function getWeather(string $trigger, string $body): Response
    {
        $location = Str::remove($trigger, $body, false);
        $apiKey = config('messenger-bots.weather_api_key');

        return Http::timeout(30)->get("https://api.weatherapi.com/v1/current.json?aqi=no&key=$apiKey&q=$location");
    }

    /**
     * @param array $weather
     * @return string
     */
    private function generateWeatherText(array $weather): string
    {
        $name = $weather['location']['name'];
        $region = $weather['location']['region'];
        $country = $weather['location']['country'];
        $temp = $weather['current']['temp_f'];
        $condition = Str::lower($weather['current']['condition']['text']);
        $wind = $weather['current']['wind_mph'];
        $windDirection = $weather['current']['wind_dir'];
        $humidity = $weather['current']['humidity'];

        return "Currently in $name, $region, $country, it is $temp degrees celsius and $condition. Winds out of the $windDirection at {$wind}mph. Humidity is $humidity%";
    }
}
