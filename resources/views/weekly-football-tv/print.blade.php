<!doctype html>
<html lang="nb">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex,follow">
    <title>Fotball på TV | Innsatt.no</title>
    <style>
        @page { size: A4 portrait; margin: 10mm; } * { box-sizing: border-box; }
        body { color: #151515; font-family: Arial, sans-serif; font-size: 9.5pt; line-height: 1.2; margin: 0 auto; max-width: 190mm; }
        .actions { margin: 10px auto; max-width: 190mm; } button, .actions a { border-radius: 4px; cursor: pointer; font: bold 14px Arial, sans-serif; padding: 8px 12px; text-decoration: none; } button { background: #176b46; border: 0; color: #fff; } .actions a { border: 1px solid #555; color: #222; margin-left: 6px; }
        .masthead { border: 2px solid #176b46; border-left-width: 7mm; margin-bottom: 3mm; padding: 3mm 4mm; } .kicker { color: #176b46; font-size: 8pt; font-weight: 700; letter-spacing: .12em; margin: 0 0 .5mm; text-transform: uppercase; } h1 { font-size: 21pt; letter-spacing: -.03em; line-height: 1; margin: 0; } .period { font-size: 11pt; font-weight: 700; margin: 1mm 0 0; } .meta { border-top: .5px solid #999; font-size: 8pt; margin: 2mm 0 0; padding-top: 1.5mm; }
        .warning { border: 1px solid #777; font-size: 8.5pt; margin: 0 0 2mm; padding: 1.5mm 2mm; } .days { column-gap: 4mm; } .days--two-columns { columns: 2; } .day, .match { break-inside: avoid; page-break-inside: avoid; } .day { border-top: 1px solid #176b46; margin: 0 0 2.2mm; padding-top: 1mm; } h2 { color: #176b46; font-size: 10pt; letter-spacing: .02em; margin: 0 0 .6mm; text-transform: uppercase; }
        .match { align-items: center; border-top: .5px solid #b6b6b6; display: grid; gap: 1.5mm; grid-template-columns: 15mm minmax(0, 1fr) 28mm; padding: 1mm 0; } .time { font-size: 12pt; font-weight: 700; letter-spacing: -.02em; } .teams { min-width: 0; } .teams strong { display: block; overflow-wrap: anywhere; } .teams span, .channel { color: #444; font-size: 8pt; } .channel { overflow-wrap: anywhere; text-align: right; } .crest { height: 3.4mm; margin-right: .7mm; object-fit: contain; vertical-align: -.65mm; width: 3.4mm; } .empty { color: #555; font-size: 8.5pt; font-style: italic; margin: 1mm 0; } .note { border-top: .75px solid #555; font-size: 8pt; margin: 2mm 0 0; padding-top: 1.25mm; }
        .days--dense .match { gap: 1mm; grid-template-columns: 13mm minmax(0, 1fr) 24mm; padding: .75mm 0; } .days--dense h2 { font-size: 9pt; } .days--dense .time { font-size: 10.5pt; }
        @media print { .no-print { display: none !important; } .masthead { border-color: #222; } .kicker, h2 { color: #222; } }
    </style>
</head>
<body>
@php
    $matchesByDay = collect($weeklyFootball['matches'])->groupBy(fn (array $match) => $match['startsAt']->format('Y-m-d'));
    $matchCount = count($weeklyFootball['matches']);
    $dayClasses = 'days'.($matchCount > 10 ? ' days--two-columns' : '').($matchCount > 16 ? ' days--dense' : '');
@endphp
    <div class="actions no-print"><button type="button" onclick="window.print()">Skriv ut</button><a href="{{ route('weekly-football-tv.index', ['fengsel' => $prison, 'uke' => $weekOffset ? 'neste' : 'denne']) }}">Tilbake</a></div>
    <header class="masthead"><p class="kicker">⚽ Innsatt.no · Fotball på TV</p><h1>{{ $weekOffset ? 'Neste uke' : 'Denne uken' }}</h1><p class="period">Uke {{ $weeklyFootball['weekStart']->isoWeek }} · {{ $weeklyFootball['weekStart']->format('d.m.Y') }}–{{ $weeklyFootball['weekEnd']->format('d.m.Y') }}</p><p class="meta">Kanalutvalg: {{ $weeklyFootball['channelSelection'] }} · Sist dataoppdatering: {{ $weeklyFootball['lastUpdated']->locale('nb')->translatedFormat('j. F Y \k\l. H.i') }}</p></header>
    @if($weeklyFootball['hasSourceFailure'])<p class="warning">KILDEFEIL: TV-guiden kunne ikke hentes for {{ count($weeklyFootball['sourceFailureDates']) }} av 7 dager. Oversikten kan være ufullstendig{{ $weeklyFootball['usingStaleData'] ? '; sist lagrede data vises der de finnes.' : '.' }}</p>@endif
    @if($weeklyFootball['hasUnpublishedDates'])<p class="warning">FORELØPIG: TV-programmet er ikke publisert for {{ count($weeklyFootball['unpublishedDates']) }} av 7 dager.</p>@endif
    @if($weeklyFootball['matches'] === [] && ! $weeklyFootball['hasSourceFailure'] && ! $weeklyFootball['hasUnpublishedDates'])<p class="warning">Ingen direktesendte kamper fra de valgte ligaene og turneringene er oppført på TV denne uken.</p>@endif
    <main class="{{ $dayClasses }}">
    @foreach(range(0, 6) as $offset)
        @php($date = $weeklyFootball['weekStart']->copy()->addDays($offset))
        @php($dayMatches = $matchesByDay->get($date->format('Y-m-d'), collect()))
        <section class="day"><h2>{{ ucfirst($date->locale('nb')->translatedFormat('l j. F')) }}</h2>
        @forelse($dayMatches as $match)<article class="match"><time class="time">{{ $match['startsAt']->format('H.i') }}</time><div class="teams"><strong>@if($match['homeLogoUrl'])<img class="crest" src="{{ $match['homeLogoUrl'] }}" alt="" onerror="this.remove()">@endif{{ preg_replace('/\s+–\s+.*/u', '', $match['name']) }} – @if($match['awayLogoUrl'])<img class="crest" src="{{ $match['awayLogoUrl'] }}" alt="" onerror="this.remove()">@endif{{ preg_replace('/^.*\s+–\s+/u', '', $match['name']) }}</strong><span>{{ $match['competition'] }}</span></div><div class="channel">{{ implode(' · ', $match['channels']) }}</div></article>@empty @if(collect($weeklyFootball['unpublishedDates'])->contains(fn ($unpublishedDate) => $unpublishedDate->isSameDay($date)))<p class="empty">TV-programmet er foreløpig ikke publisert.</p>@elseif(collect($weeklyFootball['sourceFailureDates'])->contains(fn ($failedDate) => $failedDate->isSameDay($date)))<p class="empty">TV-guiden kunne ikke hentes.</p>@else<p class="empty">Ingen oppført kamp.</p>@endif @endforelse
        </section>
    @endforeach
    </main>
    <p class="note">Tidspunktet er TV-sendingens starttid fra VG TV-guide. Avspark er ikke oppgitt i TV-dataene.</p>
    @include('partials.print-redirect', ['fallbackUrl' => route('weekly-football-tv.index', ['fengsel' => $prison, 'uke' => $weekOffset ? 'neste' : 'denne'], false), 'autoPrint' => true])
</body>
</html>
