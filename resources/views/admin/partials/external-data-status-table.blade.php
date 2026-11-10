<div class="table-responsive">
    <table class="table admin-ranking-table align-middle mb-0">
        <thead><tr><th>Tjeneste</th><th>Siste kildeforsøk</th><th>Siste vellykkede kildehenting</th><th>Forventet rytme</th><th>Status</th></tr></thead>
        <tbody>
        @foreach ($externalDataStatus as $item)
            <tr class="{{ $item['status'] === 'Data mangler' ? 'table-danger' : ($item['status'] === 'Brukbare reservedata' ? 'table-warning' : '') }}">
                <td><strong>{{ $item['service'] }}</strong>@if ($item['prison'])<br><small class="text-muted">{{ $item['prison'] }}</small>@endif</td>
                <td>{{ $item['last_attempt_at'] ? $item['last_attempt_at']->format('d.m.Y H:i') : 'Ikke registrert' }}</td>
                <td>{{ $item['last_success_at'] ? $item['last_success_at']->format('d.m.Y H:i') : 'Ikke registrert' }}</td>
                <td><small>{{ $item['cadence'] }}</small></td>
                <td><strong>{{ $item['status'] }}</strong><br><small>{{ $item['detail'] }}</small></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
