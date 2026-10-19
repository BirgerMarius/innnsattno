<p>{{ $state === 'recovered' ? 'Kilden er kontrollert og berørte data er oppdatert.' : 'Vedvarende feil påvirker brukbare data.' }}</p>
<p>Kilde: {{ $service }}</p>
<table cellpadding="6" cellspacing="0" border="1">
    <tr><th>Datadekning og funksjon</th><th>Siste vellykkede kildehenting</th><th>Feilkontroller</th><th>Første feil</th></tr>
    @foreach ($coverages as $coverage)
        <tr><td>{{ $coverage->description }}</td><td>{{ $coverage->last_success_at ?? 'Ukjent' }}</td><td>{{ $coverage->failure_count }}</td><td>{{ $coverage->first_failure_at ?? '–' }}</td></tr>
    @endforeach
</table>
