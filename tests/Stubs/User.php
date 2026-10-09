<?php

declare(strict_types=1);

namespace Jessecruz\LaravelResendInbox\Tests\Stubs;

use Illuminate\Foundation\Auth\User as Authenticatable;

final class User extends Authenticatable
{
    protected $table = 'users';

    protected $guarded = [];
}
