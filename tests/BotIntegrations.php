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
            ->get(ChuckNorrisBot::API_ENDPOINT);

        $this->assertTrue($chuck->ok());
        $this->assertArrayHasKey('value', $chuck->json());
    }

    /** @test */
    public function it_integrates_dad_joke_bot()
    {
        $dad = Http::acceptJson()
            ->timeout(30)
            ->get(DadJokeBot::API_ENDPOINT);

        $this->assertTrue($dad->ok());
        $this->assertArrayHasKey('joke', $dad->json());
    }

    /** @test */
    public function it_integrates_giphy_bot()
    {
        $giphy = Http::acceptJson()
            ->timeout(30)
            ->get(GiphyBot::API_ENDPOINT, [
                'api_key' => env('GIPHY_KEY'),
            ]);

        $this->assertTrue($giphy->ok());
        $this->assertArrayHasKey('data', $giphy->json());
        $this->assertArrayHasKey('url', $giphy->json('data'));
    }

    /** @test */
    public function it_integrates_insult_bot()
    {
        $insult = Http::acceptJson()
            ->timeout(30)
            ->get(InsultBot::API_ENDPOINT);

        $this->assertTrue($insult->ok());
        $this->assertArrayHasKey('insult', $insult->json());
    }

    /** @test */
    public function it_integrates_location_bot()
    {
        $location = Http::acceptJson()
            ->timeout(30)
            ->get(LocationBot::API_ENDPOINT_PRO.'google.com', [
                'key' => env('IP_API_KEY'),
                'fields' => 'status,country,regionName,city',
            ]);

        $this->assertTrue($location->ok());
        $this->assertArrayHasKey('status', $location->json());
        $this->assertArrayHasKey('country', $location->json());
        $this->assertArrayHasKey('regionName', $location->json());
        $this->assertArrayHasKey('city', $location->json());
    }

    /** @test */
    public function it_integrates_quotable_bot()
    {
        $quote = Http::acceptJson()
            ->timeout(30)
            ->get(QuotableBot::API_ENDPOINT);

        $this->assertTrue($quote->ok());
        $this->assertArrayHasKey('data', $quote->json());
        $this->assertArrayHasKey('quoteText', $quote->json('data')[0]);
        $this->assertArrayHasKey('quoteAuthor', $quote->json('data')[0]);
    }

    /** @test */
    public function it_integrates_random_image_bot()
    {
        $image = Http::timeout(30)->get('https://source.unsplash.com/random');

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
            ]);

        $this->assertTrue($weather->ok());
        $this->assertArrayHasKey('location', $weather->json());
        $this->assertArrayHasKey('current', $weather->json());
        $this->assertArrayHasKey('name', $weather->json('location'));
        $this->assertArrayHasKey('region', $weather->json('location'));
        $this->assertArrayHasKey('country', $weather->json('location'));
        $this->assertArrayHasKey('temp_f', $weather->json('current'));
        $this->assertArrayHasKey('wind_mph', $weather->json('current'));
        $this->assertArrayHasKey('wind_dir', $weather->json('current'));
        $this->assertArrayHasKey('humidity', $weather->json('current'));
        $this->assertArrayHasKey('condition', $weather->json('current'));
        $this->assertArrayHasKey('text', $weather->json('current')['condition']);
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
            ]);

        $this->assertTrue($wiki->ok());
        $this->assertSame($expects, $wiki->json());
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
            ]);

        $this->assertTrue($youtube->ok());
        $this->assertArrayHasKey('items', $youtube->json());
        $this->assertArrayHasKey('id', $youtube->json('items')[0]);
        $this->assertArrayHasKey('videoId', $youtube->json('items')[0]['id']);
    }
}
