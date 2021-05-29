<?php

namespace RTippin\MessengerBots;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Routing\Router;
use RTippin\Messenger\Http\Middleware\MessengerApi;

/**
 * @property-read Application $app
 */
trait RouteMap
{
    /**
     * Register our routes.
     *
     * @throws BindingResolutionException
     */
    private function registerRoutes(): void
    {
        $router = $this->app->make(Router::class);

        $router->group($this->apiRouteConfiguration(), function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        });

        if (config('messenger.routing.web.enabled')) {
            $router->group($this->webRouteConfiguration(), function () {
                $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
            });
        }
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
     * Get the Messenger WEB route group configuration array.
     *
     * @return array
     */
    private function webRouteConfiguration(): array
    {
        return [
            'domain' => config('messenger.routing.web.domain'),
            'prefix' => trim(config('messenger.routing.web.prefix'), '/'),
            'middleware' => config('messenger.routing.web.middleware'),
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
