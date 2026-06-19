<h1>⚽ Fotball-VM 2026</h1>

<p>Antall kamper: {{ count($matches) }}</p>

<table border="1" cellpadding="5">
    <tr>
        <th>Dato</th>
        <th>Kamp</th>
        <th>Gruppe</th>
        <th>Status</th>
    </tr>

    @foreach($matches as $match)
        <tr>
            <td>{{ $match['date'] }}</td>
            <td>{{ $match['home'] }} - {{ $match['away'] }}</td>
            <td>{{ $match['group'] }}</td>
            <td>{{ $match['status'] }}</td>
        </tr>
    @endforeach

</table>