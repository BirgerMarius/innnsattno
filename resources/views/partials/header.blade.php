<div class="container mt-4{{ !empty($clockChangeReminder) ? ' site-header--clock-change' : '' }}">

    <a href="{{ route('tv') }}" class="text-center mb-3 d-block" aria-label="Gå til forsiden">
        <img
            src="{{ asset('img/innsatt-logo-v2.png') }}"
            alt="INNSATT.NO"
            class="site-logo">
    </a>

    @if (!empty($clockChangeReminder))
        <aside class="clock-change-reminder" aria-label="Påminnelse om å stille klokken">
            <svg class="clock-change-reminder__clock{{ $clockChangeReminder['forward'] ? '' : ' clock-change-reminder__clock--back' }}"
                 viewBox="0 0 64 64" width="56" height="56" aria-hidden="true" focusable="false">
                <circle cx="32" cy="32" r="28" fill="none" stroke="currentColor" stroke-width="3" />
                <path d="M32 8v4m24 20h-4M32 56v-4M8 32h4" stroke="currentColor" stroke-width="3" />
                <path d="M32 32V15" class="clock-change-reminder__minute" />
                <path d="M32 32l14 8" class="clock-change-reminder__hour" />
                <circle cx="32" cy="32" r="3" fill="currentColor" />
            </svg>
            <div>
                <strong class="clock-change-reminder__title">{{ $clockChangeReminder['title'] }}</strong>
                <p>{{ $clockChangeReminder['message'] }}</p>
                <span>{{ $clockChangeReminder['time'] }} · {{ $clockChangeReminder['season'] }}</span>
            </div>
        </aside>
    @endif
</div>
