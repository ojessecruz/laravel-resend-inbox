<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Jessecruz\LaravelResendInbox\Http\Controllers\AttachmentController;
use Jessecruz\LaravelResendInbox\Http\Controllers\WebhookController;
use Jessecruz\LaravelResendInbox\Livewire\Compose;
use Jessecruz\LaravelResendInbox\Livewire\Inbox;
use Jessecruz\LaravelResendInbox\Livewire\ShowThread;

/** @var array{prefix: string, middleware: list<string>, webhook_path: string} $routes */
$routes = config('inbox.routes');

Route::post($routes['webhook_path'], WebhookController::class)->name('inbox.webhook');

Route::prefix($routes['prefix'])
    ->middleware($routes['middleware'])
    ->name('inbox.')
    ->group(function () {
        Route::get('/', Inbox::class)->name('index');
        Route::get('/compose', Compose::class)->name('compose');
        Route::get('/{thread}', ShowThread::class)->name('threads.show');
        Route::get('/messages/{message}/attachments/{attachment}', AttachmentController::class)->name('attachments.download');
    });
