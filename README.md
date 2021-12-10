# Messenger Bots

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]
[![Tests][ico-test]][link-test]
[![StyleCI][ico-styleci]][link-styleci]
[![License][ico-license]][link-license]

---

![Preview](https://raw.githubusercontent.com/RTippin/messenger-demo/master/public/examples/image2.png)

## This package is an addon for [rtippin/messenger][link-messenger]

## Notice
- This package is not required to use the bots feature built into `Messenger`.
- For more documentation on creating custom bot handlers, visit our [Chat Bots][link-bots-docs] documentation.

### Features:
- Ready to go bot action handlers that will plug into the core messenger package.
- Register only the selected bots you wish to use, or let us auto-register all bots we provide.
- Included Bot Handlers:
  - Chuck Norris Bot
  - Coin Toss Bot
  - Commands Bot
  - Dad Joke Bot
  - Giphy Bot
  - Insult Bot
  - Invite Bot
  - Joke Bot
  - Kanye West Bot
  - Knock Bot
  - Location Bot
  - Quotable Bot
  - Random Image Bot
  - Reaction Bot
  - Reply Bot
  - Rock Paper Scissors Bot
  - Roll Bot
  - Weather Bot
  - Wikipedia Bot
  - YoMomma Bot
  - Youtube Bot

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

```bash
composer require rtippin/messenger-bots
```

---

# Config

```php
'weather_api_key' => env('BOT_WEATHER_API_KEY'),
'ip_api_key' => env('BOT_LOCATION_API_KEY'),
'youtube_api_key' => env('BOT_YOUTUBE_API_KEY'),
'giphy_api_key' => env('BOT_GIPHY_API_KEY'),
'random_image_url' => env('BOT_RANDOM_IMAGE_URL', 'https://source.unsplash.com/random'),
'auto_register_all' => env('BOT_AUTO_REGISTER_ALL', false),
```

### Publish the config file

```bash
php artisan vendor:publish --tag=messenger-bots
```

- To use weather bot, you must get an API key from [Weather API][link-weather-api]
- To use YouTube bot, you must get an API key from [Google Developers Console][link-google-api]
- To use Giphy bot, you must get an API key from [Giphy][link-giphy-api]
- You may use the location bot without an API key, but for commercial use, you must get an API key from [IP API][link-ip-api]
- Random image bot will use unsplash as the default endpoint to grab a random image from. You may overwrite this endpoint.

---

# Auto Registering Handlers and Packages
- If you plan to use all the bot handlers and packaged bots provided, you can skip registering them manually by enabling the `BOT_AUTO_REGISTER_ALL` flag.

***Update your `.env`***

```dotenv
BOT_AUTO_REGISTER_ALL=true
```

---

# Manually Registering Handlers
- Inside your `MessengerServiceProvider` (or any of your providers), you must register all bot handlers you want enabled in your application.
- You can use the `MessengerBots` facade to register the handlers. Be sure you do it inside the `boot` method.

***Example:***

```php
<?php

namespace App\Providers;

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

class MessengerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        MessengerBots::registerHandlers([
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
        ]);
    }
}
```

### Registered handlers will now be available to choose when adding an action to a bot.

---

## Credits - [Richard Tippin][link-author]

## License - MIT

### Please see the [license file](LICENSE.md) for more information.


[link-author]: https://github.com/rtippin
[ico-version]: https://img.shields.io/packagist/v/rtippin/messenger-bots.svg?style=plastic&cacheSeconds=3600
[ico-downloads]: https://img.shields.io/packagist/dt/rtippin/messenger-bots.svg?style=plastic&cacheSeconds=3600
[link-test]: https://github.com/RTippin/messenger-bots/actions
[ico-test]: https://img.shields.io/github/workflow/status/rtippin/messenger-bots/tests?style=plastic
[ico-styleci]: https://styleci.io/repos/371539005/shield?style=plastic&cacheSeconds=3600
[ico-license]: https://img.shields.io/github/license/RTippin/messenger-bots?style=plastic
[link-packagist]: https://packagist.org/packages/rtippin/messenger-bots
[link-downloads]: https://packagist.org/packages/rtippin/messenger-bots
[link-license]: https://packagist.org/packages/rtippin/messenger-bots
[link-styleci]: https://styleci.io/repos/371539005
[link-messenger]: https://github.com/RTippin/messenger
[link-messenger-config]: https://github.com/RTippin/messenger/blob/1.x/config/messenger.php
[link-bots-docs]: https://github.com/RTippin/messenger/blob/1.x/docs/ChatBots.md
[link-weather-api]: https://www.weatherapi.com
[link-google-api]: https://console.developers.google.com
[link-ip-api]: https://ip-api.com
[link-giphy-api]: https://developers.giphy.com