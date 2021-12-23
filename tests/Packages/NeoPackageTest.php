<?php

namespace RTippin\MessengerBots\Tests\Packages;

use RTippin\Messenger\Facades\MessengerBots;
use RTippin\MessengerBots\Bots\CommandsBot;
use RTippin\MessengerBots\Bots\DocumentFinderBot;
use RTippin\MessengerBots\Bots\GiphyBot;
use RTippin\MessengerBots\Bots\InviteBot;
use RTippin\MessengerBots\Bots\KanyeBot;
use RTippin\MessengerBots\Bots\LocationBot;
use RTippin\MessengerBots\Bots\QuotableBot;
use RTippin\MessengerBots\Bots\RandomImageBot;
use RTippin\MessengerBots\Bots\ReactionBot;
use RTippin\MessengerBots\Bots\WeatherBot;
use RTippin\MessengerBots\Bots\WikiBot;
use RTippin\MessengerBots\Bots\YoutubeBot;
use RTippin\MessengerBots\Packages\NeoPackage;
use RTippin\MessengerBots\Tests\MessengerBotsTestCase;

class NeoPackageTest extends MessengerBotsTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        MessengerBots::registerPackagedBots([NeoPackage::class]);
    }

    /** @test */
    public function it_is_registered()
    {
        $this->assertTrue(MessengerBots::isValidPackagedBot(NeoPackage::class));
    }

    /** @test */
    public function it_gets_package_dto()
    {
        $expected = [
            'alias' => 'neo_package',
            'name' => 'Neo',
            'description' => 'Bundles internet searching and general help topic actions.',
            'avatar' => [
                'sm' => '/messenger/assets/bot-package/sm/neo_package/avatar.jpg',
                'md' => '/messenger/assets/bot-package/md/neo_package/avatar.jpg',
                'lg' => '/messenger/assets/bot-package/lg/neo_package/avatar.jpg',
            ],
            'installs' => [],
            'already_installed' => [],
        ];
        $installs = [
            CommandsBot::getDTO()->toArray(),
            DocumentFinderBot::getDTO()->toArray(),
            GiphyBot::getDTO()->toArray(),
            InviteBot::getDTO()->toArray(),
            KanyeBot::getDTO()->toArray(),
            LocationBot::getDTO()->toArray(),
            QuotableBot::getDTO()->toArray(),
            RandomImageBot::getDTO()->toArray(),
            ReactionBot::getDTO()->toArray(),
            WeatherBot::getDTO()->toArray(),
            WikiBot::getDTO()->toArray(),
            YoutubeBot::getDTO()->toArray(),
        ];
        $package = MessengerBots::getPackagedBots(NeoPackage::class);

        $this->assertSame($expected, $package->toArray());
        $this->assertSame($installs, $package->installs->toArray());
    }

    /** @test */
    public function it_passes_resolving_installs()
    {
        $installs = NeoPackage::testInstalls();

        $this->assertCount(13, $installs['resolved']);
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
            'alias' => 'neo_package',
        ])
            ->assertSuccessful();
    }
}
