<?php

namespace RTippin\MessengerBots\Tests;

use RTippin\Messenger\Facades\MessengerBots;
use RTippin\MessengerBots\MessengerBotsServiceProvider;

class AutoRegistrarTest extends MessengerBotsTestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $config = $app->get('config');

        $config->set('messenger-bots.auto_register_all', true);
    }

    /** @test */
    public function it_can_auto_register_all_bots()
    {
        foreach (MessengerBotsServiceProvider::BOTS as $bot) {
            $this->assertTrue(MessengerBots::isValidHandler($bot));
        }
    }
}
