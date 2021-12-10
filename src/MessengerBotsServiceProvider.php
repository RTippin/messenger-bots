<?php

namespace RTippin\MessengerBots;

use Illuminate\Support\ServiceProvider;
use RTippin\Messenger\Facades\MessengerBots;
use RTippin\MessengerBots\Bots\ChuckNorrisBot;
use RTippin\MessengerBots\Bots\CoinTossBot;
use RTippin\MessengerBots\Bots\CommandsBot;
use RTippin\MessengerBots\Bots\DadJokeBot;
use RTippin\MessengerBots\Bots\GiphyBot;
use RTippin\MessengerBots\Bots\InsultBot;
use RTippin\MessengerBots\Bots\InviteBot;
use RTippin\MessengerBots\Bots\JokeBot;
use RTippin\MessengerBots\Bots\KanyeBot;
use RTippin\MessengerBots\Bots\KnockBot;
use RTippin\MessengerBots\Bots\LocationBot;
use RTippin\MessengerBots\Bots\QuotableBot;
use RTippin\MessengerBots\Bots\RandomImageBot;
use RTippin\MessengerBots\Bots\ReactionBot;
use RTippin\MessengerBots\Bots\ReplyBot;
use RTippin\MessengerBots\Bots\RockPaperScissorsBot;
use RTippin\MessengerBots\Bots\RollBot;
use RTippin\MessengerBots\Bots\WeatherBot;
use RTippin\MessengerBots\Bots\WikiBot;
use RTippin\MessengerBots\Bots\YoMommaBot;
use RTippin\MessengerBots\Bots\YoutubeBot;
use RTippin\MessengerBots\Packages\GamesPackage;
use RTippin\MessengerBots\Packages\JokesterPackage;
use RTippin\MessengerBots\Packages\NeoPackage;

class MessengerBotsServiceProvider extends ServiceProvider
{
    /**
     * All bot handlers provided by this package.
     */
    const HANDLERS = [
        ChuckNorrisBot::class,
        CoinTossBot::class,
        CommandsBot::class,
        DadJokeBot::class,
        GiphyBot::class,
        InsultBot::class,
        InviteBot::class,
        JokeBot::class,
        KanyeBot::class,
        KnockBot::class,
        LocationBot::class,
        QuotableBot::class,
        RandomImageBot::class,
        ReactionBot::class,
        ReplyBot::class,
        RockPaperScissorsBot::class,
        RollBot::class,
        WeatherBot::class,
        WikiBot::class,
        YoMommaBot::class,
        YoutubeBot::class,
    ];

    /**
     * All bot packages provided by this package.
     */
    const PACKAGES = [
        GamesPackage::class,
        JokesterPackage::class,
        NeoPackage::class,
    ];

    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot(): void
    {
        if (config('messenger-bots.auto_register_all')) {
            MessengerBots::registerHandlers(self::HANDLERS);
            MessengerBots::registerPackagedBots(self::PACKAGES);
        }

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/messenger-bots.php' => config_path('messenger-bots.php'),
            ], 'messenger-bots');
        }
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/messenger-bots.php', 'messenger-bots');
    }
}
