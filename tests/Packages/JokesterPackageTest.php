<?php

namespace RTippin\MessengerBots\Tests\Packages;

use RTippin\Messenger\Facades\MessengerBots;
use RTippin\MessengerBots\Bots\ChuckNorrisBot;
use RTippin\MessengerBots\Bots\DadJokeBot;
use RTippin\MessengerBots\Bots\InsultBot;
use RTippin\MessengerBots\Bots\JokeBot;
use RTippin\MessengerBots\Bots\KnockBot;
use RTippin\MessengerBots\Bots\ReactionBot;
use RTippin\MessengerBots\Bots\YoMommaBot;
use RTippin\MessengerBots\Packages\JokesterPackage;
use RTippin\MessengerBots\Tests\MessengerBotsTestCase;

class JokesterPackageTest extends MessengerBotsTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        MessengerBots::registerPackagedBots([JokesterPackage::class]);
    }

    /** @test */
    public function it_is_registered()
    {
        $this->assertTrue(MessengerBots::isValidPackagedBot(JokesterPackage::class));
    }

    /** @test */
    public function it_gets_formatted_settings()
    {
        $expected = [
            'alias' => 'jokester_package',
            'name' => 'Jokester',
            'description' => 'A bot with much to say! Bundles many joke telling abilities.',
            'avatar' => [
                'sm' => '/messenger/assets/bot-package/sm/jokester_package/avatar.jpg',
                'md' => '/messenger/assets/bot-package/md/jokester_package/avatar.jpg',
                'lg' => '/messenger/assets/bot-package/lg/jokester_package/avatar.jpg',
            ],
            'installs' => [],
            'already_installed' => [],
        ];
        $installs = [
            MessengerBots::getHandlers(ChuckNorrisBot::class)->toArray(),
            MessengerBots::getHandlers(DadJokeBot::class)->toArray(),
            MessengerBots::getHandlers(InsultBot::class)->toArray(),
            MessengerBots::getHandlers(JokeBot::class)->toArray(),
            MessengerBots::getHandlers(KnockBot::class)->toArray(),
            MessengerBots::getHandlers(ReactionBot::class)->toArray(),
            MessengerBots::getHandlers(YoMommaBot::class)->toArray(),
        ];
        $package = MessengerBots::getPackagedBots(JokesterPackage::class);

        $this->assertSame($expected, $package->toArray());
        $this->assertSame($installs, $package->installs->toArray());
    }

    /** @test */
    public function it_can_be_installed()
    {
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.bots.packages.store', [
            'thread' => $thread->id,
        ]), [
            'alias' => 'jokester_package',
        ])
            ->assertSuccessful()
            ->assertJson([
                'actions_count' => 8,
            ]);
    }
}
