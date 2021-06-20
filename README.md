# Messenger Bots

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]
[![StyleCI][ico-styleci]][link-styleci]
[![License][ico-license]][link-license]

---

## This package is an addon for [rtippin/messenger][link-messenger]

## Notice
- This package is not required to use the bots feature built into `Messenger`.

### Features:
- Ready to go bot action handlers that will plug into the core messenger package.
- Register only the included bot handlers that you want to use with messenger.
- Included Bot Handlers:
  - Chuck Norris Bot
  - Dad Joke Bot
  - Insult Bot
  - Joke Bot
  - Kanye West Bot
  - Random Image Bot
  - Reaction Bot
  - Reply Bot
  - Rock Paper Scissors Bot
  - Roll Bot
  - Weather Bot
  - YoMomma Bot

---

# Prerequisites
- To use this package, you must already have the core [Messenger][link-messenger] package installed.
- You must have bots enabled from within the [messenger.php][link-messenger-config] config, or your `.env`.
- The built-in bot subscriber should also be enabled, unless you wish to register your own event subscriber.
- If the subscriber is queued, be sure to have your queue worker process the defined channel, `messenger-bots` is the default.

```dotenv
MESSENGER_BOTS_ENABLED=true
```
```php
'bots' => [
    'enabled' => env('MESSENGER_BOTS_ENABLED', false),
    'subscriber' => [
        'enabled' => true,
        'queued' => true,
        'channel' => 'messenger-bots',
    ],
],
```

# Installation

### Via Composer

``` bash
$ composer require rtippin/messenger-bots
```

---

# Config

```php
'weather_api_key' => env('BOT_WEATHER_API_KEY'),
'random_image_url' => env('BOT_RANDOM_IMAGE_URL', 'https://source.unsplash.com/random'),
```

### Publish the config file

``` bash
$ php artisan vendor:publish --tag=messenger-bots
```

- Currently, only the `WeatherBot` and `RandomImageBot` use config values.
- To use weather bot, you must get an API key from [Weather API][link-weather-api]
- Random image bot will use unsplash as the default endpoint to grab a random image from. You may overwrite this value within the config.

# Register Handlers
- Inside your `AppServiceProvider` (or any of your providers), you must register all bot action handlers you want to use in your app.
- You can use our `MessengerBots` facade to set handlers. Be sure you do it inside the `boot` method.

***Example:***
```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use RTippin\Messenger\Facades\MessengerBots;
use RTippin\MessengerBots\Bots\RandomImageBot;
use RTippin\MessengerBots\Bots\RockPaperScissorsBot;
use RTippin\MessengerBots\Bots\ReplyBot;
use RTippin\MessengerBots\Bots\RollBot;
use RTippin\MessengerBots\Bots\ReactionBot;
use RTippin\MessengerBots\Bots\WeatherBot;
use RTippin\MessengerBots\Bots\ChuckNorrisBot;
use RTippin\MessengerBots\Bots\DadJokeBot;
use RTippin\MessengerBots\Bots\InsultBot;
use RTippin\MessengerBots\Bots\KanyeBot;
use RTippin\MessengerBots\Bots\YoMommaBot;
use RTippin\MessengerBots\Bots\JokeBot;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        MessengerBots::setHandlers([
            ChuckNorrisBot::class,
            DadJokeBot::class,
            InsultBot::class,
            JokeBot::class,
            KanyeBot::class,
            RandomImageBot::class,
            ReactionBot::class,
            ReplyBot::class,            
            RockPaperScissorsBot::class,
            RollBot::class,
            WeatherBot::class,
            YoMommaBot::class,
        ]);
    }
}
```

### Registered handlers will now be available to choose when adding an action to a bot.

# Creating Custom Bot Handlers

```
//TODO
```


## Credits - [Richard Tippin][link-author]

## License - MIT

### Please see the [license file](LICENSE.md) for more information.


[link-author]: https://github.com/rtippin
[ico-version]: https://img.shields.io/packagist/v/rtippin/messenger-bots.svg?style=plastic&cacheSeconds=3600
[ico-downloads]: https://img.shields.io/packagist/dt/rtippin/messenger-bots.svg?style=plastic&cacheSeconds=3600
[ico-styleci]: https://styleci.io/repos/371539005/shield?style=plastic&cacheSeconds=3600
[ico-license]: https://img.shields.io/github/license/RTippin/messenger-bots?style=plastic
[link-packagist]: https://packagist.org/packages/rtippin/messenger-bots
[link-downloads]: https://packagist.org/packages/rtippin/messenger-bots
[link-license]: https://packagist.org/packages/rtippin/messenger-bots
[link-styleci]: https://styleci.io/repos/371539005
[link-messenger]: https://github.com/RTippin/messenger
[link-messenger-config]: https://github.com/RTippin/messenger/blob/master/config/messenger.php
[link-weather-api]: https://www.weatherapi.com/