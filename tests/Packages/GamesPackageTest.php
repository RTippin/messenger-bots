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
    public function it_is_registered()
    {
        $this->assertTrue(MessengerBots::isValidPackagedBot(GamesPackage::class));
    }

    /** @test */
    public function it_gets_package_dto()
    {
        $expected = [
            'alias' => 'games_package',
            'name' => 'Games',
            'description' => 'Bundles games you can play with the bot.',
            'avatar' => [
                'sm' => '/messenger/assets/bot-package/sm/games_package/avatar.gif',
                'md' => '/messenger/assets/bot-package/md/games_package/avatar.gif',
                'lg' => '/messenger/assets/bot-package/lg/games_package/avatar.gif',
            ],
            'installs' => [],
            'already_installed' => [],
        ];
        $installs = [
            CoinTossBot::getDTO()->toArray(),
            RockPaperScissorsBot::getDTO()->toArray(),
            RollBot::getDTO()->toArray(),
        ];
        $package = GamesPackage::getDTO();

        $this->assertSame($expected, $package->toArray());
        $this->assertSame($installs, $package->installs->toArray());
    }

    /** @test */
    public function it_passes_resolving_installs()
    {
        $installs = GamesPackage::testInstalls();

        $this->assertCount(3, $installs['resolved']);
        $this->assertCount(0, $installs['failed']);
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
            ->assertSuccessful();
    }
}
