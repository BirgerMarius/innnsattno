<!doctype html>
<html lang="nb"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ $competitionName }} – utskrift | Innsatt.no</title>
<style>
@page { size: A4 portrait; margin: 10mm; }
* { box-sizing: border-box; } body { color:#111; font-family:Arial,sans-serif; font-size:9pt; line-height:1.2; margin:0 auto; max-width:190mm; }
header { border-bottom:1.5px solid #111; display:flex; justify-content:space-between; margin-bottom:3.5mm; padding-bottom:2.5mm; } h1 { font-size:17pt; margin:0; } h2 { border-bottom:1px solid #555; break-after:avoid; font-size:12pt; margin:0 0 2mm; padding-bottom:1mm; page-break-after:avoid; } h3 { font-size:10pt; margin:0 0 1.5mm; }
table { border-collapse:collapse; table-layout:fixed; width:100%; } th,td { border-bottom:.5px solid #aaa; overflow-wrap:anywhere; padding:1mm 1.2mm; text-align:left; } th { font-size:8pt; text-transform:uppercase; } thead { display:table-header-group; } tr,.tie { break-inside:avoid; page-break-inside:avoid; }
.actions { margin:10px auto; max-width:190mm; } button { background:#176b36; border:0; border-radius:4px; color:#fff; cursor:pointer; font-weight:bold; padding:8px 14px; } .meta { font-size:8.5pt; text-align:right; } .center { text-align:center; } .team { width:34%; } .number { text-align:center; width:7%; } .emblem { height:3.5mm; margin-right:1mm; object-fit:contain; vertical-align:middle; width:3.5mm; }
.page-break { break-before:page; page-break-before:always; } .fixtures { display:grid; gap:5mm; grid-template-columns:repeat(2,minmax(0,1fr)); } .match { border-bottom:.5px solid #aaa; break-inside:avoid; page-break-inside:avoid; padding:1.4mm 0; } .phase { color:#444; font-size:8pt; } .knockout-round { break-inside:avoid; page-break-inside:avoid; margin:0 0 4mm; } .tie { border:1px solid #999; margin:0 0 2mm; padding:1.5mm; } .leg { border-top:.5px solid #ddd; padding:1mm 0; } .leg:first-child { border-top:0; padding-top:0; } .aggregate { font-weight:bold; margin:1mm 0 0; } .warning,.empty { border:1px solid #999; padding:2.5mm; }
@media print { .no-print { display:none !important; } body { font-size:8.5pt; } th,td { padding:.8mm 1mm; } h2 { margin-bottom:1.5mm; } }
</style></head><body>
<div class="actions no-print"><button type="button" onclick="window.print()">Skriv ut</button></div>
<header><div><strong>Innsatt.no</strong><h1>{{ $competitionName }}</h1><span>Sesongen {{ $seasonLabel }}</span></div><div class="meta">Generert {{ $generatedAt->locale('nb')->translatedFormat('j. F Y \k\l. H.i') }}</div></header>
@if($apiError)<p class="warning">Oppdaterte data kunne ikke lastes akkurat nå{{ $usingStaleData ? '. Viser sist lagrede data.' : '.' }}</p>@endif
<section><h2>Ligafasetabell</h2>@if(count($standings))<table><thead><tr><th class="number">#</th><th class="team">Lag</th><th class="number">K</th><th class="number">V</th><th class="number">U</th><th class="number">T</th><th class="center">Mål</th><th class="number">MF</th><th class="number">P</th></tr></thead><tbody>@foreach($standings as $row)<tr><td class="number">{{ $row['rank'] ?? '–' }}</td><td>@if($row['emblemUrl'])<img class="emblem" src="{{ $row['emblemUrl'] }}" alt="" onerror="this.remove()">@endif{{ $row['teamName'] }}</td><td class="number">{{ $row['played'] ?? '–' }}</td><td class="number">{{ $row['wins'] ?? '–' }}</td><td class="number">{{ $row['draws'] ?? '–' }}</td><td class="number">{{ $row['losses'] ?? '–' }}</td><td class="center">{{ $row['goalsFor'] ?? '–' }}-{{ $row['goalsAgainst'] ?? '–' }}</td><td class="number">{{ $row['goalDifference'] ?? '–' }}</td><td class="number"><strong>{{ $row['points'] ?? '–' }}</strong></td></tr>@endforeach</tbody></table>@else<p class="empty">Tabellen er ikke publisert ennå.</p>@endif</section>
<section class="page-break"><h2>Kamper</h2><div class="fixtures"><div><h3>Siste resultater</h3>@forelse($printResults as $match)<div class="match"><div class="phase">{{ $match['round'] ?: 'Ferdigspilt' }} · {{ $match['dateLabel'] }}</div><div>{{ $match['homeTeam'] }} <strong>{{ $match['homeScore'] ?? '–' }}–{{ $match['awayScore'] ?? '–' }}</strong> {{ $match['awayTeam'] }}</div></div>@empty<p class="empty">Ingen ferdigspilte kamper de siste 7 døgnene.</p>@endforelse</div><div><h3>Kommende kamper</h3>@forelse($printFixtures as $match)<div class="match"><div class="phase">{{ $match['startsAt']->locale('nb')->translatedFormat('D j. M \k\l. H.i') }}@if($match['round']) · {{ $match['round'] }}@endif</div><div>{{ $match['homeTeam'] }} – {{ $match['awayTeam'] }}</div></div>@empty<p class="empty">Ingen kamper er satt opp de neste 7 døgnene.</p>@endforelse</div></div></section>
<section class="page-break"><h2>Sluttspill</h2>
@forelse($knockoutRounds as $round)
    <div class="knockout-round"><h3>{{ $round['label'] }}</h3>
        @foreach($round['ties'] as $tie)
            <div class="tie">
                @foreach($tie['legs'] as $index => $match)
                    <div class="leg"><div class="phase">{{ count($tie['legs']) > 1 ? 'Kamp '.($index + 1).' · ' : '' }}{{ $match['dateLabel'] }}</div><div>@if($match['homeEmblemUrl'])<img class="emblem" src="{{ $match['homeEmblemUrl'] }}" alt="" onerror="this.remove()">@endif{{ $match['homeTeam'] }} {{ $match['homeScore'] ?? '–' }}–{{ $match['awayScore'] ?? '–' }} @if($match['awayEmblemUrl'])<img class="emblem" src="{{ $match['awayEmblemUrl'] }}" alt="" onerror="this.remove()">@endif{{ $match['awayTeam'] }}</div>@if($match['homeOvertimeScore'] !== null || $match['awayOvertimeScore'] !== null)<div class="phase">Etter ekstraomg.</div>@elseif($match['homePenaltyScore'] !== null || $match['awayPenaltyScore'] !== null)<div class="phase">Etter straffesparkkonkurranse.</div>@endif</div>
                @endforeach
                @if($tie['aggregateAvailable'])<p class="aggregate">Sammenlagt: {{ $tie['homeAggregateScore'] ?? '–' }}–{{ $tie['awayAggregateScore'] ?? '–' }}</p>@endif
                @if($tie['winnerTeamName'])<p class="aggregate">Går videre: {{ $tie['winnerTeamName'] }}</p>@endif
            </div>
        @endforeach
    </div>
@empty
    <p class="empty">Sluttspillkampene er ikke publisert i datagrunnlaget ennå.</p>
@endforelse
</section>
<script>const returnUrl=@json($returnUrl, JSON_UNESCAPED_SLASHES);let returned=false;function back(){if(!returned){returned=true;window.location.replace(returnUrl);}}window.addEventListener('load',function(){window.addEventListener('afterprint',back,{once:true});window.print();},{once:true});</script>
</body></html>
