@php($historical = $historical ?? false)
<div class="table-responsive">
    <p class="small text-muted mb-2">«Tid siden oppdaget» viser når systemet først registrerte mangelen for denne programdatoen, ikke nødvendigvis når feilen hos VG startet.</p>
    <table class="table table-sm align-middle mb-0">
        <thead><tr><th>Kanal</th><th>Programdato</th><th>Berørte fengsler</th><th>Programstatus</th><th>Feil oppdaget</th><th>Sist kontrollert</th><th><span title="Tid siden systemet først registrerte mangelen for denne programdatoen, ikke nødvendigvis når feilen hos VG startet.">Tid siden oppdaget</span></th></tr></thead>
        <tbody>
        @foreach ($issues as $issue)
            @php($minutes = $issue['first']->diffInMinutes($issue['last']))
            <tr class="{{ $issue['source'] === 'missing' ? 'table-danger' : 'table-success' }}">
                <td><strong>{{ $issue['name'] }}</strong>@if($historical)<br><span class="badge text-bg-secondary">Historisk</span>@endif<br><details class="d-inline"><summary class="small text-muted">Tekniske detaljer</summary><code>{{ $issue['slug'] }}</code></details></td>
                <td>{{ substr($issue['date'], 8, 2).'.'.substr($issue['date'], 5, 2).'.'.substr($issue['date'], 0, 4) }}</td>
                <td>{{ implode(', ', $issue['prisons']) }}</td>
                <td><strong>{{ $issue['programStatus'] }}</strong></td>
                <td>{{ $issue['first']->format('d.m.Y H:i') }}</td>
                <td>{{ $issue['last']->format('d.m.Y H:i') }}</td>
                <td title="Tidspunktet viser når systemet først registrerte mangelen for denne programdatoen, ikke nødvendigvis når feilen hos VG startet.">{{ intdiv($minutes, 60) ? intdiv($minutes, 60).' t ' : '' }}{{ $minutes % 60 }} min</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
