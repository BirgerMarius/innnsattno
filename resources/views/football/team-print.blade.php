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
