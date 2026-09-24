<!doctype html>
<html lang="nb">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nations League – utskrift | Innsatt.no</title>
    <style>
        @page { size: A4 portrait; margin: 8mm; }
        * { box-sizing: border-box; }
        body { color: #111; font-family: Arial, sans-serif; font-size: 8.5pt; line-height: 1.15; margin: 0 auto; max-width: 194mm; }
        header { border-bottom: 1.5px solid #111; display: flex; justify-content: space-between; margin-bottom: 2.5mm; padding-bottom: 2mm; }
        h1 { font-size: 15pt; margin: 0; }
        h2 { border-bottom: 1px solid #555; break-after: avoid; font-size: 10.5pt; margin: 3mm 0 1.5mm; padding-bottom: .75mm; page-break-after: avoid; }
        h3 { font-size: 8.5pt; margin: 0 0 .75mm; }
        table { border-collapse: collapse; table-layout: fixed; width: 100%; }
        th, td { border-bottom: .5px solid #aaa; overflow-wrap: anywhere; padding: .75mm; text-align: left; }
        th { font-size: 7.5pt; text-transform: uppercase; }
        tr, .fixture, .group { break-inside: avoid; page-break-inside: avoid; }
        .number { text-align: center; }
        .emblem, .football-tv-box__emblem { height: 3.2mm; margin-right: .8mm; object-fit: contain; vertical-align: middle; width: 3.2mm; }
        .fixtures { display: grid; gap: 4mm; grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .fixtures--single { grid-template-columns: 1fr; }
        .fixture { border-bottom: .5px solid #aaa; padding: 1mm 0; }
        .fixture-teams { overflow-wrap: anywhere; }
        .fixture-meta { color: #444; font-size: 7.5pt; margin-bottom: .5mm; }
        .groups { display: grid; gap: 1.5mm 2mm; grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .group { border: 1px solid #aaa; padding: 1.25mm; }
        .group h3 { font-size: 7.5pt; line-height: 1.05; }
        .group table { font-size: 7.25pt; line-height: 1.05; }
        .group th, .group td { padding: .45mm .55mm; }
        .norway { background: #eaf3ff; font-weight: bold; }
        .actions { margin: 10px auto; max-width: 194mm; }
        button { background: #176b36; border: 0; border-radius: 4px; color: #fff; cursor: pointer; font-weight: bold; padding: 8px 14px; }
        .warning, .empty { border: 1px solid #999; padding: 2mm; }
        .football-tv-box { border: 1px solid #777; break-inside: avoid; margin: 0 0 2mm; padding: 1.5mm 2mm; page-break-inside: avoid; }
        .football-tv-box h2 { font-size: 9pt; margin: 0; }
        .football-tv-box__match { border-top: .5px solid #aaa; padding: .5mm 0; }
        .football-tv-box__match span { display: block; font-size: 7.5pt; }
        .football-tv-box__status, .football-tv-box__note { font-size: 7.5pt; margin: .75mm 0 0; }
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body>
    <div class="actions no-print"><button type="button" onclick="window.print()">Skriv ut</button></div>
    <header><div><strong>Innsatt.no</strong><h1>Nations League</h1><span>{{ $seasonLabel }}</span></div><div>Generert {{ $generatedAt->locale('nb')->translatedFormat('j. F Y \k\l. H.i') }}</div></header>
    @if($apiError)<p class="warning">Oppdaterte data kunne ikke lastes akkurat nå{{ $usingStaleData ? '. Viser sist lagrede data.' : '.' }}</p>@endif
    @include('football.partials.tv-matches-box', ['tvBoxContext' => 'nations-league-print', 'tvBoxShowSelection' => false])

    <h2>Resultater og kommende kamper</h2>
    <div class="fixtures{{ count($printResults) === 0 || count($printFixtures) === 0 ? ' fixtures--single' : '' }}">
        @if(count($printResults))
            <section><h3>Resultater siste 7 døgn</h3>@foreach($printResults as $match)<div class="fixture"><div class="fixture-meta">{{ $match['dateLabel'] }}@if($match['round']) · Runde {{ $match['round'] }}@endif</div><div class="fixture-teams">@if($match['homeEmblemUrl'])<img class="emblem" src="{{ $match['homeEmblemUrl'] }}" alt="" onerror="this.remove()">@endif{{ $match['homeTeam'] }} <strong>{{ $match['homeScore'] ?? '–' }}–{{ $match['awayScore'] ?? '–' }}</strong> @if($match['awayEmblemUrl'])<img class="emblem" src="{{ $match['awayEmblemUrl'] }}" alt="" onerror="this.remove()">@endif{{ $match['awayTeam'] }}</div></div>@endforeach</section>
        @endif
        @if(count($printFixtures))
            <section><h3>Kommende kamper neste 7 døgn</h3>@foreach($printFixtures as $match)<div class="fixture"><div class="fixture-meta">{{ $match['startsAt']->locale('nb')->translatedFormat('D j. M \k\l. H.i') }}@if($match['round']) · Runde {{ $match['round'] }}@endif</div><div class="fixture-teams">@if($match['homeEmblemUrl'])<img class="emblem" src="{{ $match['homeEmblemUrl'] }}" alt="" onerror="this.remove()">@endif{{ $match['homeTeam'] }} – @if($match['awayEmblemUrl'])<img class="emblem" src="{{ $match['awayEmblemUrl'] }}" alt="" onerror="this.remove()">@endif{{ $match['awayTeam'] }}</div></div>@endforeach</section>
        @endif
        @if(count($printResults) === 0 && count($printFixtures) === 0)<p class="empty">Ingen ferdigspilte eller kommende kamper i syvdagersvinduene.</p>@endif
    </div>

    <h2>Norges gruppe</h2>
    @if($norwayGroup)<table><thead><tr><th>#</th><th>Lag</th><th class="number">K</th><th class="number">MF</th><th class="number">P</th><th>Mulighet</th></tr></thead><tbody>@foreach($norwayGroup['rows'] as $team)<tr class="{{ (int) $team['teamId'] === $norwayTeamId ? 'norway' : '' }}"><td>{{ $team['rank'] ?? '–' }}</td><td>@if($team['emblemUrl'])<img class="emblem" src="{{ $team['emblemUrl'] }}" alt="" onerror="this.remove()">@endif{{ $team['teamName'] }}</td><td class="number">{{ $team['played'] ?? '–' }}</td><td class="number">{{ $team['goalDifference'] ?? '–' }}</td><td class="number">{{ $team['points'] ?? '–' }}</td><td>{{ $team['rule']['name'] ?? '' }}</td></tr>@endforeach</tbody></table>@else<p class="empty">Norges gruppe er ikke publisert ennå.</p>@endif

    <h2>Alle grupper</h2>
    <div class="groups">@foreach($standingsGroups as $group)<section class="group"><h3>{{ preg_replace('/^.*?, (League [A-D]), Group (\d+)$/', '$1 · gruppe $2', $group['stageName'] ?? 'Gruppe '.$group['groupName']) }}</h3><table><thead><tr><th>#</th><th>Lag</th><th class="number">K</th><th class="number">P</th></tr></thead><tbody>@foreach($group['rows'] as $team)<tr class="{{ (int) $team['teamId'] === $norwayTeamId ? 'norway' : '' }}"><td>{{ $team['rank'] ?? '–' }}</td><td>@if($team['emblemUrl'])<img class="emblem" src="{{ $team['emblemUrl'] }}" alt="" onerror="this.remove()">@endif{{ $team['teamName'] }}</td><td class="number">{{ $team['played'] ?? '–' }}</td><td class="number">{{ $team['points'] ?? '–' }}</td></tr>@endforeach</tbody></table></section>@endforeach</div>
    @include('partials.print-redirect', ['fallbackUrl' => $returnUrl, 'autoPrint' => true])
</body>
</html>
