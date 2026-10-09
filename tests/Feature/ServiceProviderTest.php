<?php

declare(strict_types=1);

use Illuminate\Support\ServiceProvider;

it('offers the publish tags the readme documents', function (string $tag) {
    expect(ServiceProvider::publishableGroups())->toContain($tag);
})->with([
    'resend-inbox-config',
    'resend-inbox-migrations',
    'resend-inbox-views',
    'resend-inbox-translations',
]);

it('lets the app override a single component', function () {
    $override = resource_path('views/vendor/inbox/components/badge.blade.php');
    @mkdir(dirname($override), recursive: true);
    file_put_contents($override, '<span class="app-badge">{{ $slot }}</span>');

    try {
        expect(view('inbox::components.badge', ['slot' => 'Oi'])->render())->toContain('app-badge')
            ->and(view()->exists('inbox::components.button'))->toBeTrue();
    } finally {
        unlink($override);
    }
});
