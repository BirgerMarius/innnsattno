@php($preview = $change['preview'] ?? [])
<div class="admin-planned-preview{{ !empty($preview['hidden']) ? ' admin-planned-preview--hidden' : '' }}">
    @if (($preview['kind'] ?? null) === 'clock')
        @include('partials.clock-change-reminder', ['clockChangeReminder' => $preview['reminder'], 'compact' => true])
    @elseif (($preview['kind'] ?? null) === 'theme')
        <div class="admin-planned-preview__theme front-page-theme front-page-theme--{{ $preview['theme']['id'] ?? 'standard' }}">
            <span>Forsiden</span><strong>{{ $preview['theme']['name'] ?? 'Standardtema' }}</strong>
        </div>
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
