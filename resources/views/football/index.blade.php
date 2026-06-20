<h1>⚽ Fotball-VM 2026</h1>

<h2>Dagens kamper</h2>

@if(count($todayMatches) > 0)

<table border="1" cellpadding="5">
    <tr>
        <th>Tid</th>
        <th>Kamp</th>
    </tr>

    @foreach($todayMatches as $match)
        <tr>
            <td>{{ substr($match['date'], 11, 5) }}</td>
    <td>

@if($match['status'] == 'finished')

    {{ $match['home'] }}
    {{ $match['homeScore'] }}
    -
    {{ $match['awayScore'] }}
    {{ $match['away'] }}

@else

    {{ $match['home'] }}
    -
    {{ $match['away'] }}

@endif


</td>
        </tr>
    @endforeach

</table>

@else

<p>Ingen kamper i dag.</p>

@endif

<h2>Siste resultater</h2>

<table border="1" cellpadding="5">

@foreach($finishedMatches as $match)

<tr>
    <td>{{ $match['home'] }}</td>
    <td>{{ $match['homeScore'] }} - {{ $match['awayScore'] }}</td>
    <td>{{ $match['away'] }}</td>
</tr>

@endforeach

</table>

<h2>Kommende kamper</h2>

<table border="1" cellpadding="5">

@foreach($upcomingMatches as $match)

<tr>
    <td>{{ $match['date'] }}</td>
    <td>{{ $match['home'] }} - {{ $match['away'] }}</td>
</tr>

@endforeach

</table>

<h2>Gruppetabeller</h2>

@foreach($groups as $groupName => $group)

<h3>Gruppe {{ $groupName }}</h3>

<table border="1" cellpadding="5">

<tr>
    <th>#</th>
    <th>Lag</th>
    <th>K</th>
    <th>P</th>
</tr>

@foreach($group as $team)

<tr>
    <td>{{ $team['rank'] }}</td>
    <td>{{ $team['name'] }}</td>
    <td>{{ $team['played'] }}</td>
    <td>{{ $team['points'] }}</td>
</tr>

@endforeach

</table>

<br>

@endforeach

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