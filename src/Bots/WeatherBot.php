<?php

namespace RTippin\MessengerBots\Bots;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RTippin\Messenger\Actions\Bots\BotActionHandler;
use Throwable;

class WeatherBot extends BotActionHandler
{
    /**
     * Endpoint we gather data from.
     */
    const API_ENDPOINT = 'https://api.weatherapi.com/v1/current.json?aqi=no';

    /**
     * The bots settings.
     *
     * @return array
     */
    public static function getSettings(): array
    {
        return [
            'alias' => 'weather',
            'description' => 'Get the current weather for the given location. [ !w {location} ]',
            'name' => 'Weather',
            'unique' => true,
            'triggers' => ['!w', '!weather'],
            'match' => 'starts:with:caseless',
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
                $this->composer()->emitTyping()->message($this->generateWeatherText($weather->json()));

                return;
            }
        }

        $this->sendInvalidSelectionMessage();

        $this->releaseCooldown();
    }

    /**
     * @throws Throwable
     */
    private function sendInvalidSelectionMessage(): void
    {
        $this->composer()->emitTyping()->message('Please select a valid location, i.e. ( !w Orlando )');
    }

    /**
     * @param string $location
     * @return Response
     */
    private function getWeather(string $location): Response
    {
        $apiKey = '&key='.config('messenger-bots.weather_api_key');
        $query = '&q='.$location;

        return Http::timeout(15)->get(self::API_ENDPOINT.$query.$apiKey);
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

        return "Currently in $name, $region, $country, it is $temp degrees fahrenheit and $condition. Winds out of the $windDirection at {$wind}mph. Humidity is $humidity%";
    }
}
