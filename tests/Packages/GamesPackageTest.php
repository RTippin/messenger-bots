<?php

namespace RTippin\MessengerBots\Tests\Packages;

use RTippin\Messenger\Facades\MessengerBots;
use RTippin\MessengerBots\Bots\CoinTossBot;
use RTippin\MessengerBots\Bots\RockPaperScissorsBot;
use RTippin\MessengerBots\Bots\RollBot;
use RTippin\MessengerBots\Packages\GamesPackage;
use RTippin\MessengerBots\Tests\MessengerBotsTestCase;

class GamesPackageTest extends MessengerBotsTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        MessengerBots::registerPackagedBots([GamesPackage::class]);
    }

    /** @test */
    public function it_gets_formatted_settings()
    {
        $expected = [
            'alias' => 'games_package',
            'name' => 'Games',
            'description' => 'Bundles games you can play with the bot.',
            'avatar' => [
                'sm' => '/messenger/assets/bot-package/sm/games_package/avatar.png',
                'md' => '/messenger/assets/bot-package/md/games_package/avatar.png',
                'lg' => '/messenger/assets/bot-package/lg/games_package/avatar.png',
            ],
            'installs' => [
                MessengerBots::getHandlers(CoinTossBot::class)->toArray(),
                MessengerBots::getHandlers(RockPaperScissorsBot::class)->toArray(),
                MessengerBots::getHandlers(RollBot::class)->toArray(),
            ],
        ];

        $this->assertSame($expected, MessengerBots::getPackagedBots(GamesPackage::class)->toArray());
    }

    /** @test */
    public function it_can_be_installed()
    {
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.bots.packages.store', [
            'thread' => $thread->id,
        ]), [
            'alias' => 'games_package',
        ])
            ->assertSuccessful()
            ->assertJson([
                'actions_count' => 3,
            ]);
    }
}
