@php
    $standingRules = $standingRules ?? collect($standings ?? [])
        ->map(fn (array $team) => $team['rule'] ?? null)
        ->filter(fn ($rule) => is_array($rule) && is_string($rule['name'] ?? null) && trim($rule['name']) !== '')
        ->map(fn (array $rule) => ['code' => $rule['code'] ?? $rule['name'], 'name' => trim($rule['name'])])
        ->unique('code')
        ->values();
    $compactStandingRules = $compactStandingRules ?? false;
@endphp

@if($standingRules->isNotEmpty())
    <p class="football-standing-rules">
        <strong>Tegnforklaring:</strong>
        @foreach($standingRules as $rule)
            <span class="football-standing-rules__item">{{ $compactStandingRules ? ($rule['marker'] ?? '?') : '•' }} {{ $rule['name'] }}</span>
        @endforeach
        <span class="football-standing-rules__note">{{ $compactStandingRules ? 'Gjelder tabellplass, ikke sikret utfall.' : 'Viser hva tabellplassen gir ifølge SportsNext, ikke at laget har sikret utfallet.' }}</span>
    </p>
@endif
