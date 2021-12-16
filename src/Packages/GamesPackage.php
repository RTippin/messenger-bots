<?php

namespace RTippin\MessengerBots\Packages;

use RTippin\Messenger\Support\PackagedBot;
use RTippin\MessengerBots\Bots\CoinTossBot;
use RTippin\MessengerBots\Bots\RockPaperScissorsBot;
use RTippin\MessengerBots\Bots\RollBot;

class GamesPackage extends PackagedBot
{
    /**
     * The packages settings.
     *
     * @return array
     */
    public static function getSettings(): array
    {
        return [
            'alias' => 'games_package',
            'description' => 'Bundles games you can play with the bot.',
            'name' => 'Games',
            'avatar' => __DIR__.'/../../assets/games_package_avatar.gif',
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
            CoinTossBot::class => [
                'cooldown' => 15,
            ],
            RockPaperScissorsBot::class => [
                'cooldown' => 15,
            ],
            RollBot::class => [
                'cooldown' => 15,
            ],
        ];
    }
}
