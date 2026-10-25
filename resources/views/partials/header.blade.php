<div class="container mt-4 site-header site-header--has-message">
    <div class="site-header__brand-row">
        @include('partials.oslo-clock')

        <a href="{{ route('tv') }}" class="site-header__logo-link text-center mb-3 d-block" aria-label="Gå til forsiden">
            <img
                src="{{ asset('img/innsatt-logo-v2.png') }}"
                alt="INNSATT.NO"
                class="site-logo">
        </a>

        <div class="site-header__message-slot" data-header-message>
            @include('partials.header-message')
        </div>
    </div>
</div>
