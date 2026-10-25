<div class="container mt-4 site-header{{ !empty($clockChangeReminder) ? ' site-header--clock-change' : '' }}">
    <div class="site-header__brand-row">
        @include('partials.oslo-clock')

        <a href="{{ route('tv') }}" class="site-header__logo-link text-center mb-3 d-block" aria-label="Gå til forsiden">
            <img
                src="{{ asset('img/innsatt-logo-v2.png') }}"
                alt="INNSATT.NO"
                class="site-logo">
        </a>

        @if (!empty($clockChangeReminder))
            <div class="site-header__reminder-slot">
                @include('partials.clock-change-reminder', ['compact' => true])
            </div>
        @endif
    </div>
</div>
