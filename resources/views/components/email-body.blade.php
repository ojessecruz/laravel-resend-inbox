{{--
    An email's HTML in a sandboxed iframe: no scripts, links open in a new
    tab, and a CSP blocks remote resources (tracking pixels) until images
    are allowed. allow-same-origin without allow-scripts only lets the
    parent measure the height. Props: html, allowImages.
--}}
@props(['html', 'allowImages' => false])

@php
    $csp = $allowImages
        ? "default-src 'none'; img-src https: http: data:; style-src 'unsafe-inline' https:; font-src https: data:"
        : "default-src 'none'; img-src data:; style-src 'unsafe-inline'";

    $document = '<!doctype html><html><head><meta charset="utf-8">'
        .'<meta http-equiv="Content-Security-Policy" content="'.$csp.'">'
        .'<base target="_blank">'
        .'<style>html,body{margin:0}body{padding:4px;font:14px/1.5 system-ui,sans-serif;color:#18181b;background:#fff;overflow-wrap:anywhere}img{max-width:100%;height:auto}</style>'
        .'</head><body>'.$html.'</body></html>';
@endphp

<iframe
    sandbox="allow-same-origin allow-popups allow-popups-to-escape-sandbox"
    srcdoc="{{ $document }}"
    title="{{ __('inbox::inbox.message.body') }}"
    {{ $attributes->merge(['class' => 'block min-h-24 w-full rounded-lg bg-white']) }}
    x-data
    x-on:load="$el.style.height = ($el.contentDocument?.documentElement.scrollHeight ?? 400) + 'px'"
></iframe>
