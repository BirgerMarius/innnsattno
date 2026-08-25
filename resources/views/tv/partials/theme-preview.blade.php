@if (!empty($theme) && !empty($isThemePreview))
    <aside class="front-page-theme-preview" aria-label="Designforhåndsvisning">
        <span>Designforhåndsvisning</span>
        <strong>{{ $theme['name'] }}</strong>
        <a href="{{ route('design-test.index') }}">Alle varianter</a>
    </aside>
@endif
