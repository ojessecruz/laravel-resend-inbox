<section {{ $attributes->merge(['class' => 'app-card']) }}>
    @isset($header)<header class="app-card-header">{{ $header }}</header>@endisset
    {{ $slot }}
</section>
