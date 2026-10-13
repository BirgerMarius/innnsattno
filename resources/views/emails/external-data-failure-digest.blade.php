<p>Eksterne datakildefeil fra {{ $periodStart }} til {{ $periodEnd }}.</p>

<table cellpadding="6" cellspacing="0" border="1">
    <thead>
        <tr>
            <th>Tjeneste</th><th>Operasjon</th><th>Feiltype</th><th>Antall</th><th>Første</th><th>Siste</th><th>Status etter siste feil</th><th>Representativ feilmelding</th>
        </tr>
    </thead>
    <tbody>
    @foreach ($groups as $group)
        <tr>
            <td>{{ $group['service'] }}</td><td>{{ $group['operation'] }}</td><td>{{ $group['failure_kind'] }}</td><td>{{ $group['count'] }}</td><td>{{ $group['first_at'] }}</td><td>{{ $group['last_at'] }}</td><td>{{ $group['recovery'] }}</td><td>{{ $group['summary'] }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
