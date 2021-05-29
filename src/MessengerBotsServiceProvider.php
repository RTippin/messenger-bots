<?php

namespace RTippin\MessengerBots;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use RTippin\MessengerBots\Listeners\BotSubscriber;
use RTippin\MessengerBots\Models\Bot;
use RTippin\MessengerBots\Policies\BotPolicy;

class MessengerBotsServiceProvider extends ServiceProvider
{
    use RouteMap;

    /**
     * The policy mappings for messenger bots models.
     *
     * @var array
     */
    private array $policies = [
        Bot::class => BotPolicy::class,
    ];

    /**
     * Bootstrap any package services.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        $this->registerRoutes();
        $this->registerPolicies();
        $this->registerSubscriber();

        Relation::morphMap([
            'bots' => Bot::class,
        ]);

        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

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

        $this->addBotToMessengerProviders();
    }

    /**
     * Register the application's policies.
     *
     * @return void
     * @throws BindingResolutionException
     */
    private function registerPolicies(): void
    {
        $gate = $this->app->make(Gate::class);

        foreach ($this->policies as $key => $value) {
            $gate->policy($key, $value);
        }
    }

    /**
     * Register the Event Subscribers.
     *
     * @return void
     * @throws BindingResolutionException
     */
    private function registerSubscriber(): void
    {
        $events = $this->app->make(Dispatcher::class);

        $events->subscribe(BotSubscriber::class);
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
