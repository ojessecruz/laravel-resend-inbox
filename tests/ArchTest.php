<?php

declare(strict_types=1);

arch('every file declares strict types')
    ->expect('Jessecruz\LaravelResendInbox')
    ->toUseStrictTypes();

arch('classes are final')
    ->expect('Jessecruz\LaravelResendInbox')
    ->classes()
    ->toBeFinal();

arch('no debugging leftovers')
    ->expect(['dd', 'dump', 'var_dump', 'ray'])
    ->not->toBeUsed();

arch('the package knows nothing about the host app')
    ->expect('Jessecruz\LaravelResendInbox')
    ->not->toUse('App');
