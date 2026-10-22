@php($preview = $change['preview'] ?? [])
<div class="admin-planned-preview{{ !empty($preview['hidden']) ? ' admin-planned-preview--hidden' : '' }}">
    @if (($preview['kind'] ?? null) === 'clock')
        @include('partials.clock-change-reminder', ['clockChangeReminder' => $preview['reminder'], 'compact' => true])
    @elseif (($preview['kind'] ?? null) === 'theme')
        @php($themeId = $preview['theme']['id'] ?? null)
        @if ($themeId)
            <a class="admin-theme-thumbnail" href="{{ route('design-test.preview', $themeId) }}" target="_blank" rel="noopener noreferrer" aria-label="Åpne større forhåndsvisning av {{ $preview['theme']['name'] }}">
                <img src="{{ asset('img/theme-previews/'.$themeId.'.png') }}" width="240" height="150" loading="lazy" alt="Miniatyr av forsiden med temaet {{ $preview['theme']['name'] }}">
                <span>Åpne større forhåndsvisning</span>
            </a>
        @endif
    @elseif (($preview['kind'] ?? null) === 'mourning')
        <div class="admin-planned-preview__mourning">
            <strong>{{ $preview['title'] ?? $change['title'] }}</strong>
            @if (!empty($preview['message']))<span>{{ $preview['message'] }}</span>@endif
        </div>
    @else
        <div class="admin-planned-preview__element">
            <span class="front-page-new-badge" aria-hidden="true">{{ $preview['badge'] ?? 'Nyhet' }}</span>
            <strong>{{ $preview['element'] ?? $change['description'] }}</strong>
        </div>
    @endif
    @if (!empty($preview['hidden']))<span class="admin-planned-preview__hidden">Skjules</span>@endif
</div>
