<?php

declare(strict_types=1);

namespace Jessecruz\LaravelResendInbox;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Gate;
use Jessecruz\LaravelResendInbox\Livewire\Compose;
use Jessecruz\LaravelResendInbox\Livewire\Inbox;
use Jessecruz\LaravelResendInbox\Livewire\ShowThread;
use Jessecruz\LaravelResendInbox\Support\EloquentMessageLookup;
use Jessecruz\LaravelResendInbox\Support\InboxConfig;
use Jessecruz\ResendInbox\ResendMailbox;
use Jessecruz\ResendInbox\Threading\MessageLookup;
use Jessecruz\ResendInbox\Threading\ThreadResolver;
use Livewire\Livewire;
use Resend;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

final class ResendInboxServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-resend-inbox')
            ->hasConfigFile('inbox')
            ->hasViews('inbox')
            ->hasRoute('web')
            ->hasMigrations([
                'create_inbox_threads_table',
                'create_inbox_messages_table',
            ]);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(ResendMailbox::class, fn (): ResendMailbox => new ResendMailbox(
            Resend::client(InboxConfig::string('inbox.resend.key')),
        ));

        $this->app->bind(MessageLookup::class, EloquentMessageLookup::class);

        $this->app->bind(ThreadResolver::class, fn (Application $app): ThreadResolver => new ThreadResolver(
            $app->make(MessageLookup::class),
        ));
    }

    public function packageBooted(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'inbox');
        $this->publishes([__DIR__.'/../resources/lang' => lang_path('vendor/inbox')], 'laravel-resend-inbox-translations');

        Livewire::component('inbox.index', Inbox::class);
        Livewire::component('inbox.show-thread', ShowThread::class);
        Livewire::component('inbox.compose', Compose::class);

        // Closed by default: an app that installs the package and forgets to
        // define the gate does not expose its inbox outside local. The app's
        // own Gate::define('viewInbox', ...) wins whichever boots first.
        if (! Gate::has('viewInbox')) {
            Gate::define('viewInbox', fn (mixed $user = null): bool => $this->app->environment('local'));
        }
    }
}
