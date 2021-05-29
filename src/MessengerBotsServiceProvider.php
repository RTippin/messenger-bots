<?php

namespace RTippin\MessengerBots;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use RTippin\MessengerBots\Models\Bot;

class MessengerBotsServiceProvider extends ServiceProvider
{
    /**
     * Register any package services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/messenger-bots.php', 'messenger-bots');

        $this->addBotToMessengerProviders();
    }

    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot(): void
    {
        Relation::morphMap([
            'bots' => Bot::class,
        ]);

        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

            $this->publishes([
                __DIR__.'/../config/messenger-bots.php' => config_path('messenger-bots.php'),
            ], 'messenger-faker');
        }
    }

    /**
     * Merge our bot into the existing messenger providers in config.
     */
    private function addBotToMessengerProviders(): void
    {
        config()->set('messenger.providers.bot', [
            'model' => Bot::class,
            'searchable' => false,
            'friendable' => false,
            'devices' => false,
            'default_avatar' => '/path/to/some.png',
            'provider_interactions' => [
                'can_message' => false,
                'can_search' => false,
                'can_friend' => false,
            ],
        ]);
    }
}
