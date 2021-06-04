<?php

namespace RTippin\MessengerBots;

use Illuminate\Support\ServiceProvider;

class MessengerBotsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot(): void
    {
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
