<div class="container mt-4{{ !empty($clockChangeReminder) ? ' site-header--clock-change' : '' }}">

    <a href="{{ route('tv') }}" class="text-center mb-3 d-block" aria-label="Gå til forsiden">
        <img
            src="{{ asset('img/innsatt-logo-v2.png') }}"
            alt="INNSATT.NO"
            class="site-logo">
    </a>

    @if (!empty($clockChangeReminder))
        @include('partials.clock-change-reminder')
    @endif
</div>
