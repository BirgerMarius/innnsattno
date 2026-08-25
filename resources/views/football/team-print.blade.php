<!doctype html>
<html lang="nb">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $team['teamName'] }} – kampoversikt</title>
    <link href="{{ asset('css/custom/app.css') }}?v={{ filemtime(public_path('css/custom/app.css')) }}" rel="stylesheet">
</head>
<body class="football-team-print-page">
    @php($returnUrl = route($teamRoute, $team['teamId'], false))
    <div class="football-team-print-actions no-print"><button type="button" onclick="window.print()">Skriv ut</button><a href="{{ route($teamRoute, $team['teamId']) }}">Tilbake</a></div>
    <header class="football-team-print-header">
        @if($team['emblemUrl'])<img src="{{ $team['emblemUrl'] }}" alt="" onerror="this.remove()">@endif
        <div><h1>{{ $team['teamName'] }}</h1><p>{{ $leagueName }}@if($seasonName) · {{ $seasonName }}@endif</p><p>Alle seriekamper</p></div>
    </header>
    <section class="football-team-print-stats" aria-label="Sesongen så langt">
        <strong>Sesongen så langt</strong>
        @if($seasonStats['hasFinishedMatches'])
            <p>Pl. {{ $seasonStats['keyFigures'][0]['value'] ?? '–' }} · K {{ $seasonStats['keyFigures'][1]['value'] ?? '–' }} · V {{ $seasonStats['keyFigures'][2]['value'] ?? '–' }} · U {{ $seasonStats['keyFigures'][3]['value'] ?? '–' }} · T {{ $seasonStats['keyFigures'][4]['value'] ?? '–' }} · MF {{ $seasonStats['keyFigures'][5]['value'] ?? '–' }} · MM {{ $seasonStats['keyFigures'][6]['value'] ?? '–' }} · Diff. {{ $seasonStats['keyFigures'][7]['value'] ?? '–' }} · P {{ $seasonStats['keyFigures'][8]['value'] ?? '–' }}</p>
            <p>Form: {{ implode(' ', array_column($seasonStats['form'], 'result')) }} · Hjemme {{ $seasonStats['home']['wins'] }}–{{ $seasonStats['home']['draws'] }}–{{ $seasonStats['home']['losses'] }} · Borte {{ $seasonStats['away']['wins'] }}–{{ $seasonStats['away']['draws'] }}–{{ $seasonStats['away']['losses'] }} · Clean sheets {{ $seasonStats['records']['cleanSheets'] }}</p>
            <p>Største seier: @if($seasonStats['records']['largestWin']){{ $seasonStats['records']['largestWin']['teamScore'] }}–{{ $seasonStats['records']['largestWin']['opponentScore'] }} mot {{ $seasonStats['records']['largestWin']['opponent'] }}@else–@endif · Største tap: @if($seasonStats['records']['largestLoss']){{ $seasonStats['records']['largestLoss']['teamScore'] }}–{{ $seasonStats['records']['largestLoss']['opponentScore'] }} mot {{ $seasonStats['records']['largestLoss']['opponent'] }}@else–@endif · Mest mål: @if($seasonStats['records']['highestScoringMatch']){{ $seasonStats['records']['highestScoringMatch']['teamScore'] }}–{{ $seasonStats['records']['highestScoringMatch']['opponentScore'] }} mot {{ $seasonStats['records']['highestScoringMatch']['opponent'] }}@else–@endif</p>
        @else
            <p>Ingen ferdigspilte seriekamper ennå.</p>
        @endif
    </section>
    <div class="football-team-print-list">
        @foreach($teamMatches as $match)
            <div class="football-team-print-match"><span>{{ $match['startsAt'] ? $match['startsAt']->format('d.m.') : 'Ikke satt' }} {{ $match['timeLabel'] }}</span><strong>{{ $match['isHome'] ? 'H' : 'B' }}</strong><span>{{ $match['opponent'] }}</span><b>@if($match['isFinished']){{ $match['teamScore'] ?? '–' }}–{{ $match['opponentScore'] ?? '–' }}@else{{ $match['statusLabel'] === 'Ikke startet' ? '–' : $match['statusLabel'] }}@endif</b></div>
        @endforeach
    </div>
    <p class="football-team-print-note"><strong>Merk:</strong> Dato og tidspunkt for kommende kamper kan endres. Kampoversikten oppdateres når nye opplysninger blir tilgjengelige.</p>
    <script>
        let hasReturnedToTeamPage = false;
        const teamPageUrl = @json($returnUrl);
        window.addEventListener('afterprint', function () {
            if (hasReturnedToTeamPage) return;
            hasReturnedToTeamPage = true;
            window.location.replace(teamPageUrl);
        });
        window.addEventListener('load', function () { window.print(); }, { once: true });
    </script>
</body>
</html>
