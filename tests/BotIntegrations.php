<?php

namespace RTippin\MessengerBots\Tests;

use Illuminate\Support\Facades\Http;
use Orchestra\Testbench\TestCase;
use RTippin\MessengerBots\Bots\ChuckNorrisBot;
use RTippin\MessengerBots\Bots\DadJokeBot;
use RTippin\MessengerBots\Bots\GiphyBot;
use RTippin\MessengerBots\Bots\InsultBot;
use RTippin\MessengerBots\Bots\LocationBot;
use RTippin\MessengerBots\Bots\QuotableBot;
use RTippin\MessengerBots\Bots\WeatherBot;
use RTippin\MessengerBots\Bots\WikiBot;
use RTippin\MessengerBots\Bots\YoutubeBot;

class BotIntegrations extends TestCase
{
    /** @test */
    public function it_integrates_chuck_norris_bot()
    {
        $chuck = Http::acceptJson()
            ->timeout(30)
            ->get(ChuckNorrisBot::API_ENDPOINT)
            ->throw()
            ->json();

        $this->assertArrayHasKey('value', $chuck);
    }

    /** @test */
    public function it_integrates_dad_joke_bot()
    {
        $dad = Http::acceptJson()
            ->timeout(30)
            ->get(DadJokeBot::API_ENDPOINT)
            ->throw()
            ->json();

        $this->assertArrayHasKey('joke', $dad);
    }

    /** @test */
    public function it_integrates_giphy_bot()
    {
        $giphy = Http::acceptJson()
            ->timeout(30)
            ->get(GiphyBot::API_ENDPOINT, [
                'api_key' => env('GIPHY_KEY'),
            ])
            ->throw()
            ->json();

        $this->assertArrayHasKey('data', $giphy);
        $this->assertArrayHasKey('url', $giphy['data']);
    }

    /** @test */
    public function it_integrates_insult_bot()
    {
        $insult = Http::acceptJson()
            ->timeout(30)
            ->get(InsultBot::API_ENDPOINT)
            ->throw()
            ->json();

        $this->assertArrayHasKey('insult', $insult);
    }

    /** @test */
    public function it_integrates_location_bot()
    {
        $location = Http::acceptJson()
            ->timeout(30)
            ->get(LocationBot::API_ENDPOINT_PRO.'google.com', [
                'key' => env('IP_API_KEY'),
                'fields' => 'status,country,regionName,city',
            ])
            ->throw()
            ->json();

        $this->assertArrayHasKey('status', $location);
        $this->assertArrayHasKey('country', $location);
        $this->assertArrayHasKey('regionName', $location);
        $this->assertArrayHasKey('city', $location);
    }

    /** @test */
    public function it_integrates_quotable_bot()
    {
        $quote = Http::acceptJson()
            ->timeout(30)
            ->get(QuotableBot::API_ENDPOINT)
            ->throw()
            ->json();

        $this->assertArrayHasKey('data', $quote);
        $this->assertArrayHasKey('quoteText', $quote['data'][0]);
        $this->assertArrayHasKey('quoteAuthor', $quote['data'][0]);
    }

    /** @test */
    public function it_integrates_random_image_bot()
    {
        $image = Http::timeout(30)
            ->get('https://source.unsplash.com/random')
            ->throw();

        $this->assertTrue($image->ok());
    }

    /** @test */
    public function it_integrates_weather_bot()
    {
        $weather = Http::acceptJson()
            ->timeout(30)
            ->get(WeatherBot::API_ENDPOINT, [
                'key' => env('WEATHER_KEY'),
                'q' => 'Orlando',
                'aqi' => 'no',
            ])
            ->throw()
            ->json();

        $this->assertArrayHasKey('location', $weather);
        $this->assertArrayHasKey('current', $weather);
        $this->assertArrayHasKey('name', $weather['location']);
        $this->assertArrayHasKey('region', $weather['location']);
        $this->assertArrayHasKey('country', $weather['location']);
        $this->assertArrayHasKey('temp_f', $weather['current']);
        $this->assertArrayHasKey('wind_mph', $weather['current']);
        $this->assertArrayHasKey('wind_dir', $weather['current']);
        $this->assertArrayHasKey('humidity', $weather['current']);
        $this->assertArrayHasKey('condition', $weather['current']);
        $this->assertArrayHasKey('text', $weather['current']['condition']);
    }

    /** @test */
    public function it_integrates_wiki_bot()
    {
        $expects = [
            'PHP',
            ['PHP'],
            [''],
            ['https://en.wikipedia.org/wiki/PHP'],
        ];

        $wiki = Http::acceptJson()
            ->timeout(30)
            ->get(WikiBot::API_ENDPOINT, [
                'limit' => 1,
                'search' => 'PHP',
                'action' => 'opensearch',
                'namespace' => 0,
                'format' => 'json',
            ])
            ->throw()
            ->json();

        $this->assertSame($expects, $wiki);
    }

    /** @test */
    public function it_integrates_youtube_bot()
    {
        $youtube = Http::acceptJson()
            ->timeout(30)
            ->get(YoutubeBot::API_ENDPOINT, [
                'key' => env('YOUTUBE_KEY'),
                'maxResults' => 1,
                'q' => 'Rick Rolled',
                'part' => 'id',
                'type' => 'video',
            ])
            ->throw()
            ->json();

        $this->assertArrayHasKey('items', $youtube);
        $this->assertArrayHasKey('id', $youtube['items'][0]);
        $this->assertArrayHasKey('videoId', $youtube['items'][0]['id']);
    }
}
