<!doctype html>
<html lang="nb">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,follow">
    <title>Denne ukens TV-sendte fotballkamper | Innsatt.no</title>
    <style>
        @page { size: A4 portrait; margin: 11mm; }
        * { box-sizing: border-box; }
        body { color: #111; font-family: Arial, sans-serif; font-size: 10pt; line-height: 1.25; margin: 0 auto; max-width: 188mm; }
        .actions { margin: 10px auto; max-width: 188mm; } button, .actions a { border-radius: 4px; cursor: pointer; font: bold 14px Arial, sans-serif; padding: 8px 12px; text-decoration: none; } button { background: #176b36; border: 0; color: #fff; } .actions a { border: 1px solid #555; color: #222; margin-left: 6px; }
        header { border-bottom: 1.5px solid #111; margin-bottom: 3mm; padding-bottom: 2.5mm; } h1 { font-size: 17pt; line-height: 1.1; margin: 0; } .meta { font-size: 9pt; margin: 1mm 0 0; }
        .warning { border: 1px solid #8a6412; margin: 0 0 3mm; padding: 2mm; } h2 { background: #edf5ef; border-bottom: 1px solid #52735c; break-after: avoid; font-size: 11pt; margin: 3mm 0 0; padding: 1.25mm 1.5mm; page-break-after: avoid; }
        .day, .match { break-inside: avoid; page-break-inside: avoid; } .match { border-bottom: .5px solid #aaa; display: grid; gap: 2mm; grid-template-columns: 25mm minmax(0, 1fr) 39mm; padding: 1.5mm; } .time strong, .teams strong { display: block; } .time span, .teams span, .channels { color: #444; font-size: 8.5pt; } .channels { overflow-wrap: anywhere; } .empty { color: #555; font-style: italic; margin: 1.5mm; } .note { border-top: .5px solid #777; font-size: 8pt; margin-top: 3mm; padding-top: 1.5mm; }
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body>
@php($matchesByDay = collect($weeklyFootball['matches'])->groupBy(fn (array $match) => $match['startsAt']->format('Y-m-d')))
    <div class="actions no-print"><button type="button" onclick="window.print()">Skriv ut</button><a href="{{ route('weekly-football-tv.index', ['fengsel' => $prison]) }}">Tilbake</a></div>
    <header><strong>Innsatt.no</strong><h1>Denne ukens TV-sendte fotballkamper</h1><p class="meta">Uke {{ $weeklyFootball['weekStart']->isoWeek }} · {{ $weeklyFootball['weekStart']->format('d.m.Y') }}–{{ $weeklyFootball['weekEnd']->format('d.m.Y') }} · Kanalutvalg: {{ $weeklyFootball['channelSelection'] }}<br>Sist dataoppdatering: {{ $weeklyFootball['lastUpdated']->locale('nb')->translatedFormat('j. F Y \k\l. H.i') }}</p></header>
    @if($weeklyFootball['hasSourceFailure'])<p class="warning">TV-guiden dekker ikke hele denne uken ennå eller kunne ikke hentes for {{ count($weeklyFootball['unavailableDates']) }} av 7 dager. Plakaten kan være ufullstendig{{ $weeklyFootball['usingStaleData'] ? '; sist lagrede data vises der de finnes.' : '.' }}</p>@endif
    @if($weeklyFootball['matches'] === [] && ! $weeklyFootball['hasSourceFailure'])<p class="warning">Ingen direktesendte kamper fra de valgte ligaene og turneringene er oppført på TV denne uken.</p>@endif
    @foreach(range(0, 6) as $offset)
        @php($date = $weeklyFootball['weekStart']->copy()->addDays($offset))
        @php($dayMatches = $matchesByDay->get($date->format('Y-m-d'), collect()))
        <section class="day"><h2>{{ ucfirst($date->locale('nb')->translatedFormat('l j. F')) }}</h2>
        @forelse($dayMatches as $match)<article class="match"><div class="time"><strong>{{ $match['startsAt']->format('H.i') }}</strong><span>Sendestart</span></div><div class="teams"><strong>{{ $match['name'] }}</strong><span>{{ $match['competition'] }}</span></div><div class="channels">{{ implode(' · ', $match['channels']) }}</div></article>@empty <p class="empty">Ingen oppført kamp.</p>@endforelse
        </section>
    @endforeach
    <p class="note">Tidspunktet er TV-sendingens starttid fra VG TV-guide. Avspark er ikke oppgitt i TV-dataene.</p>
    @include('partials.print-redirect', ['fallbackUrl' => route('weekly-football-tv.index', ['fengsel' => $prison], false), 'autoPrint' => true])
</body>
</html>
