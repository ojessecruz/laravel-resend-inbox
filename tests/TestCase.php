<?php

declare(strict_types=1);

namespace Jessecruz\LaravelResendInbox\Tests;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Jessecruz\LaravelResendInbox\ResendInboxServiceProvider;
use Jessecruz\LaravelResendInbox\Tests\Stubs\User;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            LivewireServiceProvider::class,
            ResendInboxServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        config()->set('inbox.domain', 'elenya.app');
        config()->set('inbox.mailboxes', ['contato@elenya.app', 'suporte@elenya.app', 'financeiro@elenya.app']);
        config()->set('inbox.sender_name', 'Jessé do Elenya');
        config()->set('inbox.resend.key', 're_test');
        config()->set('inbox.resend.webhook_secret', 'whsec_'.base64_encode('resend-test-secret'));
        config()->set('inbox.user_model', User::class);

        Schema::create('users', function ($table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_admin')->default(false);
            $table->timestamps();
        });

        foreach (File::glob(__DIR__.'/../database/migrations/*.php.stub') as $migration) {
            (include $migration)->up();
        }
    }

    protected function defineRoutes($router): void
    {
        $router->get('/login', fn (): string => 'login')->name('login');
    }

    protected function defineEnvironment($app): void
    {
        Gate::define('viewInbox', fn (User $user): bool => (bool) $user->getAttribute('is_admin'));
    }
}
