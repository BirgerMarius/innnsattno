<!doctype html>
<html lang="nb">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $leagueName }} – ukeoversikt | Innsatt.no</title>
    <style>
        @page { size: A4 portrait; margin: 8mm; }
        * { box-sizing: border-box; }
        body { color: #111; font-family: Arial, sans-serif; font-size: 9pt; line-height: 1.15; margin: 0 auto; max-width: 194mm; }
        header { align-items: flex-end; border-bottom: 1.5px solid #111; display: flex; justify-content: space-between; margin-bottom: 3mm; padding-bottom: 2mm; }
        h1 { font-size: 16pt; margin: 0; }
        h2 { border-bottom: 1px solid #555; font-size: 11pt; margin: 3mm 0 1mm; padding-bottom: 1mm; }
        p { margin: 0; }
        .meta { font-size: 8pt; text-align: right; }
        .actions { margin: 10px auto; max-width: 194mm; }
        button { background: #176b36; border: 0; border-radius: 4px; color: #fff; cursor: pointer; font-weight: bold; padding: 8px 14px; }
        table { border-collapse: collapse; table-layout: fixed; width: 100%; }
        th, td { border-bottom: .5px solid #aaa; overflow-wrap: anywhere; padding: 1mm 1.3mm; text-align: left; }
        th { font-size: 7.5pt; text-transform: uppercase; }
        thead { display: table-header-group; }
        tr { break-inside: avoid; page-break-inside: avoid; }
        .date { width: 29%; }
        .score { text-align: center; width: 12%; }
        .rank, .number { text-align: center; width: 9%; }
        .team { width: 55%; }
        .empty, .warning { border: 1px solid #aaa; padding: 2mm; }
        .warning { margin-bottom: 2mm; }
        h2 { break-after: avoid; page-break-after: avoid; }
        @media print {
            .no-print { display: none !important; }
            body { font-size: 8pt; }
            h1 { font-size: 14pt; }
            h2 { margin-top: 2.2mm; }
            th, td { padding-bottom: .65mm; padding-top: .65mm; }
        }
    </style>
</head>
<body>
    <div class="actions no-print"><button type="button" onclick="window.print()">Skriv ut</button></div>
    <header>
        <div><strong>Innsatt.no</strong><h1>{{ $leagueName }}</h1></div>
        <div class="meta">
            Generert {{ $generatedAt->locale('nb')->translatedFormat('j. F Y \k\l. H.i') }}<br>
            Uke {{ $previousWeek['number'] }} og {{ $nextWeek['number'] }}
        </div>
    </header>

    @if($apiError)
        <p class="warning">Oppdaterte data kunne ikke lastes akkurat nå{{ $usingStaleData ? '. Viser sist lagrede data.' : '.' }}</p>
    @endif

    <section>
        <h2>Resultater – uke {{ $previousWeek['number'] }} ({{ $previousWeek['start']->format('d.m') }}–{{ $previousWeek['end']->format('d.m.Y') }})</h2>
        @if(count($previousWeekResults))
            <table><thead><tr><th class="date">Dato</th><th>Hjemmelag</th><th class="score">Resultat</th><th>Bortelag</th></tr></thead><tbody>
            @foreach($previousWeekResults as $match)
                <tr><td>{{ $match['startsAt']->locale('nb')->translatedFormat('D j. M') }}</td><td>{{ $match['homeTeam'] }}</td><td class="score">{{ $match['homeScore'] ?? '–' }}–{{ $match['awayScore'] ?? '–' }}</td><td>{{ $match['awayTeam'] }}</td></tr>
            @endforeach
            </tbody></table>
        @else <p class="empty">Ingen ferdigspilte kamper forrige uke.</p> @endif
    </section>

    <section>
        <h2>Kamper – uke {{ $nextWeek['number'] }} ({{ $nextWeek['start']->format('d.m') }}–{{ $nextWeek['end']->format('d.m.Y') }})</h2>
        @if(count($nextWeekFixtures))
            <table><thead><tr><th class="date">Dato og tid</th><th>Hjemmelag</th><th>Bortelag</th></tr></thead><tbody>
            @foreach($nextWeekFixtures as $match)
                <tr><td>{{ $match['startsAt']->locale('nb')->translatedFormat('D j. M \k\l. H.i') }}</td><td>{{ $match['homeTeam'] }}</td><td>{{ $match['awayTeam'] }}</td></tr>
            @endforeach
            </tbody></table>
        @else <p class="empty">Ingen kamper er satt opp kommende uke.</p> @endif
    </section>

    <section>
        <h2>Tabell</h2>
        @if(count($standings))
            <table><thead><tr><th class="rank">Pl.</th><th class="team">Lag</th><th class="number">K</th><th class="number">MF</th><th class="number">P</th></tr></thead><tbody>
            @foreach($standings as $row)
                <tr><td class="rank">{{ $row['rank'] ?? '–' }}</td><td>{{ $row['teamName'] }}</td><td class="number">{{ $row['played'] ?? '–' }}</td><td class="number">{{ $row['goalDifference'] ?? '–' }}</td><td class="number"><strong>{{ $row['points'] ?? '–' }}</strong></td></tr>
            @endforeach
            </tbody></table>
        @else <p class="empty">Tabellen kunne ikke lastes akkurat nå.</p> @endif
    </section>

    <script>
        window.addEventListener('load', function () {
            if (!window.__innsattPrintStarted) {
                window.__innsattPrintStarted = true;
                window.print();
            }
        }, { once: true });
    </script>
</body>
</html>
