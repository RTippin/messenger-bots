<?php

namespace RTippin\MessengerBots\Bots;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RTippin\Messenger\Actions\Bots\BotActionHandler;
use RTippin\Messenger\Actions\Messages\StoreMessage;
use Throwable;

class WeatherBot extends BotActionHandler
{
    /**
     * @var StoreMessage
     */
    private StoreMessage $storeMessage;

    /**
     * WeatherBot constructor.
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
            'alias' => 'weather',
            'description' => 'Get the weather for the given location. (!w {location})',
            'name' => 'Weather Bot',
            'unique' => true,
            'triggers' => ['!w', '!weather'],
            'match' => 'starts:with',
        ];
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        $location = trim(Str::remove($this->matchingTrigger, $this->message->body, false));

        if (! empty($location)) {
            $weather = $this->getWeather($location);

            if ($weather->successful()) {
                $this->storeMessage->execute($this->message->thread, [
                    'message' => $this->generateWeatherText($weather->json()),
                ]);
            } else {
                $this->releaseCooldown();
            }
        } else {
            $this->releaseCooldown();
        }
    }

    /**
     * @param string $location
     * @return Response
     */
    private function getWeather(string $location): Response
    {
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
