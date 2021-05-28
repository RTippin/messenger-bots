<?php

namespace RTippin\MessengerBots\Tests\Fixtures;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Traits\Messageable;

class UserModel extends User implements MessengerProvider
{
    use Messageable;
    use HasFactory;

    protected $table = 'users';

    protected $guarded = [];

    protected static function newFactory(): Factory
    {
        return UserModelFactory::new();
    }
}
