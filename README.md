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
  - Commands Bot
  - Dad Joke Bot
  - Insult Bot
  - Joke Bot
  - Kanye West Bot
  - Knock Bot
  - Location Bot
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

``` bash
$ composer require rtippin/messenger-bots
```

---

# Config

```php
'weather_api_key' => env('BOT_WEATHER_API_KEY'),
'ip_api_key' => env('BOT_LOCATION_API_KEY'),
'youtube_api_key' => env('BOT_YOUTUBE_API_KEY'),
'random_image_url' => env('BOT_RANDOM_IMAGE_URL', 'https://source.unsplash.com/random'),
```

### Publish the config file

``` bash
$ php artisan vendor:publish --tag=messenger-bots
```

- Currently, only the `WeatherBot`, `LocationBot`, `YoutubeBot`, and `RandomImageBot` use config values.
- To use weather bot, you must get an API key from [Weather API][link-weather-api]
- To use youtube bot, you must get an API key from [Google Developers Console][link-google-api]
- You may use the location bot without an API key, but for commercial use, you must get an API key from [IP API][link-ip-api]
- Random image bot will use unsplash as the default endpoint to grab a random image from. You may overwrite this endpoint.

---

# Register Handlers
- Inside your `AppServiceProvider` (or any of your providers), you must register all bot action handlers you want to use in your app.
- You can use our `MessengerBots` facade to set handlers. Be sure you do it inside the `boot` method.

***Example:***

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use RTippin\Messenger\Facades\MessengerBots;
use RTippin\MessengerBots\Bots\ChuckNorrisBot;
use RTippin\MessengerBots\Bots\CommandsBot;
use RTippin\MessengerBots\Bots\DadJokeBot;
use RTippin\MessengerBots\Bots\InsultBot;
use RTippin\MessengerBots\Bots\JokeBot;
use RTippin\MessengerBots\Bots\KanyeBot;
use RTippin\MessengerBots\Bots\KnockBot;
use RTippin\MessengerBots\Bots\LocationBot;
use RTippin\MessengerBots\Bots\RandomImageBot;
use RTippin\MessengerBots\Bots\ReactionBot;
use RTippin\MessengerBots\Bots\ReplyBot;
use RTippin\MessengerBots\Bots\RockPaperScissorsBot;
use RTippin\MessengerBots\Bots\RollBot;
use RTippin\MessengerBots\Bots\WeatherBot;
use RTippin\MessengerBots\Bots\WikiBot;
use RTippin\MessengerBots\Bots\YoMommaBot;
use RTippin\MessengerBots\Bots\YoutubeBot;

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
            CommandsBot::class,
            DadJokeBot::class,
            InsultBot::class,
            JokeBot::class,
            KanyeBot::class,
            KnockBot::class,
            LocationBot::class,
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

# Creating Custom Bot Handlers

## Create your handler class and extend our [BotActionHandler][link-action-handler] abstract class.
- At the very minimum, your class must define a public `handle(): void` method and a public static `getSettings(): array` method.
- Should you need to inject dependencies, you may add your own constructor and type hint any dependencies. Your handler class will be instantiated using the laravel container.
```php
<?php

namespace App\Bots;

use RTippin\Messenger\Actions\Bots\BotActionHandler;
use Throwable;

class TestBot extends BotActionHandler
{
    /**
     * The bots settings.
     *
     * @return array
     */
    public static function getSettings(): array
    {
        return [
            'alias' => 'testing',
            'description' => 'I am a test bot handler.',
            'name' => 'McTesting!',
            'unique' => true,
            'match' => 'exact',
            'triggers' => ['!test', '!trigger'],
        ];
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        //
    }
}
```
### The `getSettings` method must define the handler `alias`, `description`, and `name`.
- `unique`, `match`, and `triggers` are optional overrides.

### alias
- Used to locate and attach your handler to a bot.

### description`
- The description of your bot handler, typically what it does.

### name
- The name of your bot handler.

### unique (optional)
- When set and true, the handler may only be used once per bot.

### triggers (optional)
- Overrides allowing the end user to set the triggers. Only the given trigger(s) will be used. Separate multiple via the pipe (|) or use an array.

### match (optional)
- Overrides allowing end user to select matching method.

### Available match methods
- `contains` - The trigger can be anywhere within a message. Cannot be part of or inside another word.
- `contains:caseless` - Same as "contains", but is case insensitive.
- `contains:any` - The trigger can be anywhere within a message, including inside another word.
- `contains:any:caseless` - Same as "contains any", but is case insensitive.
- `exact` - The trigger must match the message exactly.
- `exact:caseless` - Same as "exact", but is case insensitive.
- `starts:with` - The trigger must be the lead phrase within the message. Cannot be part of or inside another word.
- `starts:with:caseless` - Same as "starts with", but is case insensitive.

## The `handle` method
- The handle method will be executed when a matching actions trigger is associated with your bot handler.
- When your handle method is called, you will have access to many properties already set by the messenger core.
  - `$this->action` provides the current `BotAction` model that was matched to your handler.
  - `$this->thread` provides the group `Thread` model we are using.
  - `$this->message` provides the `Message` model we are using. You can also access the message sender via the owner relation `$this->message->owner`.
  - `$this->matchingTrigger` provides the trigger that was matched to the message.
  - `$this->senderIp` provides the IP of the message sender.

### Helper methods
- `getPayload(?string $key = null)`
  - If your handler stores extra data as a `payload`, it will be stored as JSON.
  - getPayload will return the decoded array, with an optional `$key` to return a specific value.
- `releaseCooldown()`
  - Calling this will remove any cooldown set on the `BotAction` model after your handle method is executed.
  - Cooldowns are optional and are set by the end user, per action. A cooldown will be started right before your handle method is executed.
  - This is helpful when your handler did not perform an action (perhaps an API call that was denied), and you can ensure any cooldowns defined on that bot action are removed.
- `composer()`
  - Returns a [MessengerComposer][link-messenger-composer] instance with the `TO` preset with the messages thread, and `FROM` preset as the bot the action belongs to.
    - Please note that each time you call `$this->composer()`, you will be given a new instance.
  - This has the most common use cases for what a bot may do (message, send an image/audio/document message, add message reaction, knock)
    - `silent()` Silences any realtime broadcast.
    - `message()` Sends a message. Optional reply to ID and extra payload.
    - `image()` Uploads an image message. Optional reply to ID and extra payload.
    - `document()` Uploads a document message. Optional reply to ID and extra payload.
    - `audio()` Uploads an audio message. Optional reply to ID and extra payload.
    - `reaction()` Adds a reaction to the message.
    - `knock()` Sends a knock to the current thread.
    - `read()` Marks the thread read for the `FROM` or set participant.
    - `emitTyping()` Emits a typing presence client event. (Bot typing).
    - `emitStopTyping()` Emits a stopped typing presence client event. (Bot stopped typing).
    - `emitRead` Emits a read presence client event. (Bot read message).
  
### Example handler that sends a message and adds a reaction when triggered.
```php
    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        $this->composer()->emitTyping()->message('I can send messages and react!');

        $this->composer()->reaction($this->message, '💯');
    }
```

## Custom payloads
- To allow your handler to store user generated data for later use, you must define the validation rules we will use when the end user is attaching your handler to a bot.
- All fields you define in your rules will be serialized and stored as json on the `BotAction` model your handler gets attached to.
- The rules and optional error message overrides use laravels validator under the hood, just how a form request class implements them.

### Payload methods
- `rules()`
  - Return the validation rules used when adding the action to a bot. Any rules you define will have their keys/values stored in the actions payload. Return an empty array if you have no extra data to validate or store.
- `errorMessages()`
  - If you define extra validation rules, you may also define the validator error messages here.
- `serializePayload(?array $payload)`
  - This method will be called when storing your handler data to the database. By default, this method `json_encode()` your rule keys and their data.
  - You may overwrite this method if you plan to do further sanitization/manipulation of data before it is stored.
### Example reply bot, allowing end user to store up to 5 replies to the handler.
```php
<?php

namespace App\Bots;

use RTippin\Messenger\Actions\Bots\BotActionHandler;
use Throwable;

class ReplyBot extends BotActionHandler
{
    /**
     * The bots settings.
     *
     * @return array
     */
    public static function getSettings(): array
    {
        return [
            'alias' => 'reply',
            'description' => 'Reply with the given response(s).',
            'name' => 'Reply',
        ];
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'replies' => ['required', 'array', 'min:1', 'max:5'],
            'replies.*' => ['required', 'string'],
        ];
    }

    /**
     * @return array
     */
    public function errorMessages(): array
    {
        return [
            'replies.*.required' => 'Reply is required.',
            'replies.*.string' => 'A reply must be a string.',
        ];
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        $this->composer()->emitTyping();

        foreach ($this->getPayload('replies') as $reply) {
            $this->composer()->message($reply);
        }
    }
}
```

## Authorization
- To authorize the end user add the action handler to a bot, you must define the 'authorize()' method and return bool. If unauthorized, the handler will be hidden from appearing in the available handlers list while adding actions to a bot. This does NOT authorize being triggered once added to a bot action.
```php
<?php

namespace App\Bots;

use RTippin\Messenger\Actions\Bots\BotActionHandler;
use Throwable;

class TestBot extends BotActionHandler
{
    /**
     * The bots settings.
     *
     * @return array
     */
    public static function getSettings(): array
    {
        return [
            'alias' => 'testing',
            'description' => 'I am a test bot handler.',
            'name' => 'McTesting!',
            'unique' => true,
            'match' => 'exact',
            'triggers' => ['!test', '!trigger'],
        ];
    }
    
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return auth()->user()->isAdmin();
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        $this->composer()->emitTyping()->message('I need authorization!');
    }
}
```
### Now register your new handler
- Once you are ready to make your handler available for use, head back to your `AppServiceProvider` and add your handler class to your `MessengerBots::setHandlers()` array
```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use RTippin\Messenger\Facades\MessengerBots;
use App\Bots\TestBot;


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
            TestBot::class,
            // all other handlers
        ]);
    }
}
```

## Please checkout our bot handlers included in this package for more examples.

### You may also view our base interface [ActionHandler][link-action-interface]

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
[link-action-handler]: https://github.com/RTippin/messenger/blob/master/src/Actions/Bots/BotActionHandler.php
[link-action-interface]: https://github.com/RTippin/messenger/blob/master/src/Contracts/ActionHandler.php
[link-messenger-composer]: https://github.com/RTippin/messenger/blob/master/src/Support/MessengerComposer.php
[link-weather-api]: https://www.weatherapi.com
[link-google-api]: https://console.developers.google.com
[link-ip-api]: https://ip-api.com