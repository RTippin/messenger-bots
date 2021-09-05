<?php

namespace RTippin\MessengerBots;

use App\Bots\SystemMessageBot;
use Illuminate\Support\ServiceProvider;
use RTippin\Messenger\Facades\MessengerBots;
use RTippin\MessengerBots\Bots\ChuckNorrisBot;
use RTippin\MessengerBots\Bots\CoinTossBot;
use RTippin\MessengerBots\Bots\CommandsBot;
use RTippin\MessengerBots\Bots\DadJokeBot;
use RTippin\MessengerBots\Bots\InsultBot;
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

class MessengerBotsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot(): void
    {
        if (config('messenger-bots.auto_register_all')) {
            $this->registerAllBots();
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

    /**
     * When enabled, register all of our provided bots for the application.
     */
    private function registerAllBots(): void
    {
        MessengerBots::registerHandlers([
            ChuckNorrisBot::class,
            CoinTossBot::class,
            CommandsBot::class,
            DadJokeBot::class,
            InsultBot::class,
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
        ]);
    }
}
