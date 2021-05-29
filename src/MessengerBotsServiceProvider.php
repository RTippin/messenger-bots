<?php

namespace RTippin\MessengerBots;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use RTippin\Messenger\Http\Middleware\MessengerApi;
use RTippin\MessengerBots\Models\Bot;

class MessengerBotsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        $this->registerRoutes();

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

    /**
     * Register our middleware.
     *
     * @throws BindingResolutionException
     */
    private function registerRoutes(): void
    {
        $router = $this->app->make(Router::class);

        $router->group($this->apiRouteConfiguration(), function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        });
    }

    /**
     * Get the Messenger API route group configuration array.
     *
     * @return array
     */
    private function apiRouteConfiguration(): array
    {
        return [
            'domain' => config('messenger.routing.api.domain'),
            'prefix' => trim(config('messenger.routing.api.prefix'), '/'),
            'middleware' => $this->mergeApiMiddleware(config('messenger.routing.api.middleware')),
        ];
    }

    /**
     * Prepend our API middleware, merge additional
     * middleware, append throttle middleware.
     *
     * @param $middleware
     * @return array
     */
    private function mergeApiMiddleware($middleware): array
    {
        $merged = array_merge([MessengerApi::class], is_array($middleware) ? $middleware : [$middleware]);

        array_push($merged, 'throttle:messenger-api');

        return $merged;
    }
}
