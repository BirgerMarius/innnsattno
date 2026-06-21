<h1>⚽ Fotball-VM 2026</h1>

<style>
.top-section {
    display: flex;
    gap: 20px;
    align-items: flex-start;
}

.top-box {
    width: 32%;
}
</style>

<a href="/fotball-utskrift"
   target="_blank"
   class="btn btn-primary mb-3">
   🖨 Utskriftsversjon
</a>

<div class="top-section">

<div class="top-box">

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

</div>

<div class="top-box">

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

</div>

<div class="top-box">

<h2>Kommende kamper</h2>

<table border="1" cellpadding="5">

@foreach($upcomingMatches as $match)

<tr>
    <td>{{ $match['date'] }}</td>
    <td>{{ $match['home'] }} - {{ $match['away'] }}</td>
</tr>

@endforeach

</table>

</div>

</div>

<h2>Gruppetabeller</h2>

<style>
.groups {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
}

.group-box {
    width: 31%;
}

.group-box table {
    width: 100%;
    font-size: 11px;
}

.group-box h3 {
    margin-top: 2px;
    margin-bottom: 2px;
}
</style>

<div class="groups">

@foreach($groups as $groupName => $group)

<div class="group-box">

<h3>Gruppe {{ $groupName }}</h3>

<table border="1" cellpadding="5">

<tr>
   <th>#</th>
<th>Lag</th>
<th>K</th>
<th>V</th>
<th>U</th>
<th>T</th>
<th>M-F</th>
<th>P</th>
</tr>

@foreach($group as $team)

<tr>
    <td>{{ $team['rank'] }}</td>
<td>{{ $team['name'] }}</td>
<td>{{ $team['played'] }}</td>
<td>{{ $team['wins'] }}</td>
<td>{{ $team['draws'] }}</td>
<td>{{ $team['losses'] }}</td>
<td>{{ $team['goalsFor'] }}-{{ $team['goalsAgainst'] }}</td>
<td>{{ $team['points'] }}</td>
</tr>

@endforeach

</table>

<br>

</div>

@endforeach

</div>