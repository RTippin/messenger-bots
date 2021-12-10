<?php

namespace RTippin\MessengerBots\Packages;

use RTippin\Messenger\MessengerBots;
use RTippin\Messenger\Support\PackagedBot;
use RTippin\MessengerBots\Bots\ChuckNorrisBot;
use RTippin\MessengerBots\Bots\DadJokeBot;
use RTippin\MessengerBots\Bots\InsultBot;
use RTippin\MessengerBots\Bots\JokeBot;
use RTippin\MessengerBots\Bots\KnockBot;
use RTippin\MessengerBots\Bots\ReactionBot;
use RTippin\MessengerBots\Bots\YoMommaBot;

class JokesterPackage extends PackagedBot
{
    /**
     * The packages settings.
     *
     * @return array
     */
    public static function getSettings(): array
    {
        return [
            'alias' => 'jokester_package',
            'description' => 'A bot with much to say! Bundles many joke telling abilities.',
            'name' => 'Jokester',
            'avatar' => __DIR__.'/../../assets/jokester_package_avatar.jpg',
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
            ChuckNorrisBot::class => [
                'cooldown' => 15,
                'match' => MessengerBots::MATCH_EXACT_CASELESS,
                'triggers' => ['!chuck', '!norris'],
            ],
            DadJokeBot::class => [
                'cooldown' => 15,
                'match' => MessengerBots::MATCH_CONTAINS_CASELESS,
                'triggers' => ['!dad', 'dad', 'daddy', 'father'],
            ],
            InsultBot::class => [
                'cooldown' => 120,
                'match' => MessengerBots::MATCH_CONTAINS_ANY_CASELESS,
                'triggers' => ['!insult', 'fuck', 'asshole', 'bitch', 'shit', 'cunt'],
            ],
            JokeBot::class => [
                'cooldown' => 15,
                'match' => MessengerBots::MATCH_EXACT_CASELESS,
                'triggers' => ['!joke'],
            ],
            KnockBot::class => [
                'cooldown' => 300,
                'match' => MessengerBots::MATCH_CONTAINS_CASELESS,
                'triggers' => ['!knock', 'knock', 'ding', 'dong'],
            ],
            ReactionBot::class => [
                [
                    'match' => MessengerBots::MATCH_CONTAINS_CASELESS,
                    'reaction' => '💩',
                    'triggers' => ['shit', 'poop', 'crap'],
                ],
                [
                    'match' => MessengerBots::MATCH_CONTAINS_CASELESS,
                    'reaction' => '🤣',
                    'triggers' => ['lmao', 'rofl', 'lol', 'ha', 'lmfao', 'lulz', 'haha'],
                ],
            ],
            YoMommaBot::class => [
                'cooldown' => 15,
                'match' => MessengerBots::MATCH_CONTAINS_CASELESS,
                'triggers' => ['!yomomma', 'mom', 'mommy', 'mother'],
            ],
        ];
    }
}
