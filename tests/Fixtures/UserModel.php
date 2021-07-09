<?php

namespace RTippin\MessengerBots\Tests\Fixtures;

use Illuminate\Foundation\Auth\User;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Traits\Messageable;

class UserModel extends User implements MessengerProvider
{
    use Messageable;

    protected $table = 'users';

    protected $guarded = [];
}
