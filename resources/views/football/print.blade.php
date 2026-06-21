<p>Oppdatert: {{ date('d.m.Y H:i') }}</p>

<h1>⚽ Fotball-VM 2026</h1>

<style>
.top-section {
    display: flex;
    gap: 30px;
    align-items: flex-start;
}

.top-box {
    width: 48%;
}
</style>

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

</div>

<div style="page-break-before: always;"></div>

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

.playoff-columns {
    display: flex;
    gap: 10px;
}

.playoff-column {
    width: 32%;
}

.playoff-column table {
    width: 100%;
    font-size: 10px;
    margin-bottom: 10px;
}

.playoff-column h3 {
    margin-top: 5px;
    margin-bottom: 3px;
}

</style>

@if(!$groupStageFinished)
<h2>Gruppetabeller</h2>


<div class="groups">

@foreach($groups as $groupName => $group)

<div class="group-box">

<h3>Gruppe {{ $groupName }}</h3>

<table border="1" cellpadding="3">

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

</div>
@endforeach

</div>
@endif

@if($groupStageFinished)

<h2>Sluttspill</h2>

<div class="playoff-columns">

<div class="playoff-column">

<h3>32-delsfinaler</h3>

<table border="1" cellpadding="3">

@foreach($playoffStages['roundOf32'] ?? [] as $match)
<tr>
    <td>{{ substr($match['date'], 0, 16) }}</td>
    <td>{{ $match['home'] }} - {{ $match['away'] }}</td>
</tr>
@endforeach

</table>

</div>

<div class="playoff-column">

<h3>16-delsfinaler</h3>

<table border="1" cellpadding="3">

@foreach($playoffStages['roundOf16'] ?? [] as $match)
<tr>
    <td>{{ substr($match['date'], 0, 16) }}</td>
    <td>{{ $match['home'] }} - {{ $match['away'] }}</td>
</tr>
@endforeach

</table>

<h3>Kvartfinaler</h3>

<table border="1" cellpadding="3">

@foreach($playoffStages['quarterfinal'] ?? [] as $match)
<tr>
    <td>{{ substr($match['date'], 0, 16) }}</td>
    <td>{{ $match['home'] }} - {{ $match['away'] }}</td>
</tr>
@endforeach

</table>

</div>

<div class="playoff-column">

<h3>Semifinaler</h3>

<table border="1" cellpadding="3">

@foreach($playoffStages['semifinal'] ?? [] as $match)
<tr>
    <td>{{ substr($match['date'], 0, 16) }}</td>
    <td>{{ $match['home'] }} - {{ $match['away'] }}</td>
</tr>
@endforeach

</table>

<h3>Bronsefinale</h3>

<table border="1" cellpadding="3">

@foreach($playoffStages['3rdPlaceFinal'] ?? [] as $match)
<tr>
    <td>{{ substr($match['date'], 0, 16) }}</td>
    <td>{{ $match['home'] }} - {{ $match['away'] }}</td>
</tr>
@endforeach

</table>

<h3>Finale</h3>

<table border="1" cellpadding="3">

@foreach($playoffStages['final'] ?? [] as $match)
<tr>
    <td>{{ substr($match['date'], 0, 16) }}</td>
    <td>{{ $match['home'] }} - {{ $match['away'] }}</td>
</tr>
@endforeach

</table>

</div>

</div>

@endif

<script>
window.print();
</script>