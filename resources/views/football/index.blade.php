<h1>⚽ Fotball-VM 2026</h1>

<p>Antall kamper: {{ count($matches) }}</p>

<table border="1" cellpadding="5">
    <tr>
        <th>Dato</th>
        <th>Kamp</th>
        <th>Resultat</th>
        <th>Gruppe</th>
        <th>Status</th>
    </tr>

    @foreach(array_slice($matches, 0, 20) as $match)
        <tr>
            <td>{{ $match['date'] }}</td>

            <td>
                {{ $match['home'] }} - {{ $match['away'] }}
            </td>

            <td>
                @if($match['homeScore'] !== null)
                    {{ $match['homeScore'] }} - {{ $match['awayScore'] }}
                @else
                    -
                @endif
            </td>

            <td>{{ $match['group'] }}</td>

            <td>{{ $match['status'] }}</td>
        </tr>
    @endforeach

</table>