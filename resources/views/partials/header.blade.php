<div class="container mt-4 site-header site-header--has-message">
    <div class="site-header__brand-row">
        <a href="{{ route('prison.activity-rooms', app(\App\Services\PrisonContext::class)->prison()['slug']) }}" class="oslo-clock-link" target="_blank" rel="noopener" aria-label="Åpne Aktivitetsrom – test i ny fane">
            @include('partials.oslo-clock')
        </a>

        <a href="{{ $prisonHomeUrl }}" class="site-header__logo-link text-center mb-3 d-block" aria-label="Gå til forsiden">
            <img
                src="{{ asset('img/innsatt-logo-v2.png') }}"
                alt="INNSATT.NO"
                class="site-logo">
        </a>

        <div class="site-header__message-slot" data-header-message @if ($headerMessageNextChangeAt !== null) data-header-message-refresh-at="{{ $headerMessageNextChangeAt->toIso8601String() }}" @endif>
            @include('partials.header-message')
        </div>
    </div>
</div>
