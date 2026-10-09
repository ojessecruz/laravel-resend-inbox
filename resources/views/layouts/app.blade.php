{{--
    Neutral standalone layout. Point config('inbox.layout') at your admin
    layout instead, so the inbox gets your navigation and compiled CSS.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? __('inbox::inbox.titles.inbox') }}</title>
</head>
<body class="bg-white text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
    <main class="mx-auto max-w-5xl px-4 py-8">
        {{ $slot }}
    </main>
</body>
</html>
