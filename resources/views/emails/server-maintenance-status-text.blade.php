@if ($kind === 'error')
Kontrollen av serveroppdateringer feilet.
Feil: {{ $payload['error'] ?? 'Ukjent feil' }}
@elseif ($kind === 'recovered')
Kontrollen av serveroppdateringer lykkes igjen.
@else
Det finnes {{ count($payload['packages'] ?? []) }} tilgjengelige serveroppdateringer.
@foreach ($payload['packages'] ?? [] as $package)
- {{ $package['name'] }}: {{ $package['installed_version'] }} -> {{ $package['candidate_version'] }}@if ($package['held']) (på hold)@endif
@endforeach
@endif

Sist vellykket kontroll: {{ $payload['last_success_at'] ?? 'ikke registrert' }}
Ingen pakker er installert automatisk.
