<?php

namespace RTippin\MessengerBots\Packages;

use RTippin\Messenger\MessengerBots;
use RTippin\Messenger\Support\PackagedBot;
use RTippin\MessengerBots\Bots\CommandsBot;
use RTippin\MessengerBots\Bots\DocumentFinderBot;
use RTippin\MessengerBots\Bots\GiphyBot;
use RTippin\MessengerBots\Bots\InviteBot;
use RTippin\MessengerBots\Bots\LocationBot;
use RTippin\MessengerBots\Bots\NukeBot;
use RTippin\MessengerBots\Bots\RandomImageBot;
use RTippin\MessengerBots\Bots\ReactionBot;
use RTippin\MessengerBots\Bots\WeatherBot;
use RTippin\MessengerBots\Bots\WikiBot;
use RTippin\MessengerBots\Bots\YoutubeBot;

class NeoPackage extends PackagedBot
{
    const COOL_TRIGGERS = ['cool', 'nice', 'awesome', 'sweet', '100', ':100:', 'wow'];

    /**
     * The packages settings.
     *
     * @return array
     */
    public static function getSettings(): array
    {
        return [
            'alias' => 'neo_package',
            'description' => 'Bundles internet searching and general help topic actions.',
            'name' => 'Neo',
            'avatar' => __DIR__.'/../../assets/neo_package_avatar.jpg',
        ];
    }

    /**
     * The handlers and their settings to install.
     *
     * @return array
     */
    public static function installs(): array
    {
        return [
            CommandsBot::class => [
                'cooldown' => 120,
            ],
            DocumentFinderBot::class => [
                'cooldown' => 15,
                'limit' => 10,
            ],
            GiphyBot::class => [
                'cooldown' => 15,
            ],
            InviteBot::class => [
                'cooldown' => 120,
                'lifetime_minutes' => 15,
            ],
            LocationBot::class => [
                'cooldown' => 15,
            ],
            NukeBot::class => [
                'admin_only' => true,
                'cooldown' => 0,
            ],
//            QuotableBot::class => [
//                'cooldown' => 15,
//            ],
            RandomImageBot::class => [
                'cooldown' => 60,
                'match' => MessengerBots::MATCH_EXACT_CASELESS,
                'triggers' => ['!image', '!picture'],
            ],
            ReactionBot::class => [
                [
                    'match' => MessengerBots::MATCH_CONTAINS_CASELESS,
                    'reaction' => '👍',
                    'triggers' => self::COOL_TRIGGERS,
                ],
                [
                    'match' => MessengerBots::MATCH_CONTAINS_CASELESS,
                    'reaction' => '💯',
                    'triggers' => self::COOL_TRIGGERS,
                ],
            ],
            WeatherBot::class => [
                'cooldown' => 15,
            ],
            WikiBot::class => [
                'cooldown' => 15,
                'limit' => 3,
            ],
            YoutubeBot::class => [
                'cooldown' => 15,
                'limit' => 1,
            ],
        ];
    }
}
