<p>@if ($kind === 'error') Kontrollen av serveroppdateringer feilet. @elseif ($kind === 'recovered') Kontrollen av serveroppdateringer lykkes igjen. @else Det finnes {{ count($payload['packages'] ?? []) }} tilgjengelige serveroppdateringer. @endif</p>
@if (in_array($kind, ['update', 'reminder']))<ul>@foreach ($payload['packages'] ?? [] as $package)<li><strong>{{ $package['name'] }}</strong>: {{ $package['installed_version'] }} → {{ $package['candidate_version'] }}@if ($package['held']) (på hold)@endif</li>@endforeach</ul>@endif
@if ($kind === 'error')<p>Feil: {{ $payload['error'] ?? 'Ukjent feil' }}</p>@endif
<p>Sist vellykket kontroll: {{ $payload['last_success_at'] ?? 'ikke registrert' }}. Ingen pakker er installert automatisk.</p>
