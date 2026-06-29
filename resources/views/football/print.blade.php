<!DOCTYPE html>
<html lang="nb">
<head>
    <meta charset="UTF-8">
    <title>Fotball-VM 2026 - INNSATT.NO</title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
</head>
<body>

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
    font-size: 9px;
    line-height: 0.9;
}

.group-box td,
.group-box th {
    padding: 1px;
}

.group-box h3 {
    margin-top: 0;
    margin-bottom: 0;
    font-size: 14px;
}
.playoff-columns {
    display: table;
    width: 100%;
    table-layout: fixed;
}

.playoff-column {
    display: table-cell;
    width: 33.333%;
    vertical-align: top;
    padding: 0 6px;

    break-inside: avoid;
    page-break-inside: avoid;
}

.playoff-column table {
    width: 100%;
    font-size: 10px;
    margin-bottom: 10px;
    border-collapse: collapse;
}

.playoff-column td,
.playoff-column th {
    padding: 2px;
    vertical-align: top;
}

.playoff-column h3 {
    margin: 4px 0;
}

.playoff-column tr {
    break-inside: avoid;
    page-break-inside: avoid;
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

<span style="white-space: nowrap;">

@if(!empty($match['homeFlagCode']))
<img src="https://flagcdn.com/24x18/{{ $match['homeFlagCode'] }}.png"
     style="display:inline-block;vertical-align:middle;width:24px;height:18px;">
@endif

{{ $match['home'] }}
{{ $match['homeScore'] }}
-

{{ $match['awayScore'] }}

@if(!empty($match['awayFlagCode']))
<img src="https://flagcdn.com/24x18/{{ $match['awayFlagCode'] }}.png"
     style="display:inline-block;vertical-align:middle;width:24px;height:18px;">
@endif

{{ $match['away'] }}

</span>

@else

<span style="white-space: nowrap;">

@if(!empty($match['homeFlagCode']))
<img src="https://flagcdn.com/24x18/{{ $match['homeFlagCode'] }}.png"
     style="display:inline-block;vertical-align:middle;width:24px;height:18px;">
@endif

{{ $match['home'] }} -

@if(!empty($match['awayFlagCode']))
<img src="https://flagcdn.com/24x18/{{ $match['awayFlagCode'] }}.png"
     style="display:inline-block;vertical-align:middle;width:24px;height:18px;">
@endif

{{ $match['away'] }}

</span>

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
    <td>
        @if(!empty($match['homeFlagCode']))
        <img src="https://flagcdn.com/24x18/{{ $match['homeFlagCode'] }}.png"
             style="display:inline-block;vertical-align:middle;width:24px;height:18px;">
        @endif
        {{ $match['home'] }}
    </td>

    <td>{{ $match['homeScore'] }} - {{ $match['awayScore'] }}</td>

    <td>
        @if(!empty($match['awayFlagCode']))
        <img src="https://flagcdn.com/24x18/{{ $match['awayFlagCode'] }}.png"
             style="display:inline-block;vertical-align:middle;width:24px;height:18px;">
        @endif
        {{ $match['away'] }}
    </td>
</tr>

@endforeach

</table>

</div>

</div>

<h2>Sluttspill</h2>

<div class="playoff-columns">

<div class="playoff-column">

<h3>32-delsfinaler</h3>

<table border="1" cellpadding="3">

@foreach($playoffStages['roundOf32'] ?? [] as $match)
<tr>
    <td>{{ substr($match['date'], 0, 16) }}</td>
    <td>
        @if(!empty($match['homeFlagCode']))
        <img src="https://flagcdn.com/24x18/{{ $match['homeFlagCode'] }}.png" style="width:24px;height:18px;">
        @endif

        {{ $match['home'] }} -

        @if(!empty($match['awayFlagCode']))
        <img src="https://flagcdn.com/24x18/{{ $match['awayFlagCode'] }}.png" style="width:24px;height:18px;">
        @endif

        {{ $match['away'] }}
    </td>
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
    <td>
        @if(!empty($match['homeFlagCode']))
        <img src="https://flagcdn.com/24x18/{{ $match['homeFlagCode'] }}.png" style="width:24px;height:18px;">
        @endif
        {{ $match['home'] }} -
        @if(!empty($match['awayFlagCode']))
        <img src="https://flagcdn.com/24x18/{{ $match['awayFlagCode'] }}.png" style="width:24px;height:18px;">
        @endif
        {{ $match['away'] }}
    </td>
</tr>
@endforeach

</table>

<h3>Kvartfinaler</h3>

<table border="1" cellpadding="3">

@foreach($playoffStages['quarterfinal'] ?? [] as $match)
<tr>
    <td>{{ substr($match['date'], 0, 16) }}</td>
    <td>
        @if(!empty($match['homeFlagCode']))
        <img src="https://flagcdn.com/24x18/{{ $match['homeFlagCode'] }}.png" style="width:24px;height:18px;">
        @endif
        {{ $match['home'] }} -
        @if(!empty($match['awayFlagCode']))
        <img src="https://flagcdn.com/24x18/{{ $match['awayFlagCode'] }}.png" style="width:24px;height:18px;">
        @endif
        {{ $match['away'] }}
    </td>
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
    <td>
        @if(!empty($match['homeFlagCode']))
        <img src="https://flagcdn.com/24x18/{{ $match['homeFlagCode'] }}.png" style="width:24px;height:18px;">
        @endif
        {{ $match['home'] }} -
        @if(!empty($match['awayFlagCode']))
        <img src="https://flagcdn.com/24x18/{{ $match['awayFlagCode'] }}.png" style="width:24px;height:18px;">
        @endif
        {{ $match['away'] }}
    </td>
</tr>
@endforeach

</table>

<h3>Bronsefinale</h3>

<table border="1" cellpadding="3">

@foreach($playoffStages['3rdPlaceFinal'] ?? [] as $match)
<tr>
    <td>{{ substr($match['date'], 0, 16) }}</td>
    <td>
        @if(!empty($match['homeFlagCode']))
        <img src="https://flagcdn.com/24x18/{{ $match['homeFlagCode'] }}.png" style="width:24px;height:18px;">
        @endif
        {{ $match['home'] }} -
        @if(!empty($match['awayFlagCode']))
        <img src="https://flagcdn.com/24x18/{{ $match['awayFlagCode'] }}.png" style="width:24px;height:18px;">
        @endif
        {{ $match['away'] }}
    </td>
</tr>
@endforeach

</table>

<h3>Finale</h3>

<table border="1" cellpadding="3">

@foreach($playoffStages['final'] ?? [] as $match)
<tr>
    <td>{{ substr($match['date'], 0, 16) }}</td>
    <td>
        @if(!empty($match['homeFlagCode']))
        <img src="https://flagcdn.com/24x18/{{ $match['homeFlagCode'] }}.png" style="width:24px;height:18px;">
        @endif
        {{ $match['home'] }} -
        @if(!empty($match['awayFlagCode']))
        <img src="https://flagcdn.com/24x18/{{ $match['awayFlagCode'] }}.png" style="width:24px;height:18px;">
        @endif
        {{ $match['away'] }}
    </td>
</tr>
@endforeach

</table>

</div>

</div>



<script>
window.print();
</script>

</body>
</html>