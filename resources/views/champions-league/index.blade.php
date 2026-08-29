@extends('layouts.app')

@section('title', 'Champions League 2026/27 | INNSATT.NO')

@push('styles')
<style>
    .cl-page { background: #f6f7f9; min-height: 100vh; }
    .cl-hero, .cl-section { background: #fff; border: 1px solid #dde2e8; border-radius: 6px; }
    .cl-muted, .cl-status { color: #667085; }
    .cl-title-row, .cl-team, .cl-match-team { align-items: center; display: flex; }
    .cl-title-row { flex-wrap: wrap; }
    .cl-beta { border: 1px solid #b7c2cf; border-radius: 999px; color: #445364; font-size: .75rem; font-weight: 700; letter-spacing: .02em; margin-left: .5rem; padding: .15rem .55rem; text-transform: uppercase; }
    .cl-table th { background: #eef2f6; color: #2d3748; font-size: .82rem; white-space: nowrap; }
    .cl-table td { vertical-align: middle; white-space: nowrap; }
    .cl-team { gap: .5rem; min-width: 170px; white-space: normal; }
    .cl-emblem { height: 24px; object-fit: contain; width: 24px; }
    .cl-match-emblem { flex: 0 0 auto; height: 18px; object-fit: contain; width: 18px; }
    .cl-match { border-top: 1px solid #e6eaf0; padding: .65rem 0; }
    .cl-match:first-child { border-top: 0; }
    .cl-match-row { display: flex; justify-content: space-between; }
    .cl-match-row > * + * { margin-left: 1rem; }
    .cl-match-teams { display: grid; gap: .35rem; }
    .cl-match-team { gap: .4rem; width: fit-content; }
    .cl-score { font-weight: 700; min-width: 3.75rem; text-align: right; }
    .cl-empty { background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 6px; color: #536173; margin-bottom: 0; padding: 1rem; }
    .cl-phase { color: #536173; font-size: .875rem; }
    .cl-team-link { color: inherit; text-decoration: none; }
    .cl-team-link:hover, .cl-team-link:focus { text-decoration: underline; }
    .cl-bracket { display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); overflow-x: auto; }
    .cl-bracket-round { background: #f8fafc; border: 1px solid #dde2e8; border-radius: 5px; min-width: 230px; padding: .75rem; }
    .cl-tie { background: #fff; border: 1px solid #dfe5ec; border-radius: 4px; break-inside: avoid; margin-top: .65rem; padding: .55rem; page-break-inside: avoid; }
    .cl-leg { border-top: 1px solid #edf0f4; padding: .4rem 0; }
    .cl-leg:first-child { border-top: 0; padding-top: 0; }
    .cl-aggregate { color: #334155; font-size: .875rem; font-weight: 700; margin: .45rem 0 0; }
    @media (max-width: 575.98px) { .cl-hero, .cl-section { border-left: 0; border-right: 0; border-radius: 0; } .cl-bracket { display: block; } .cl-bracket-round { margin-bottom: 1rem; } }
</style>
@endpush

@section('content')
<div class="cl-page"><div class="container py-4 py-md-5">
    @include('partials.header')

    <section class="cl-hero p-3 p-md-4 mb-3">
        <div class="d-flex flex-wrap align-items-start justify-content-between">
            <div><div class="cl-title-row mb-2"><h1 class="h2 mb-0">Champions League</h1><span class="cl-beta">Beta</span></div>
                <p class="lead mb-2">Sesongen 2026/27 · tabell, kamper og sluttspill hentet fra VG/Schibsted sitt sports-API.</p>
                @if($lastUpdated)<p class="cl-muted mb-0">Sist oppdatert: {{ $lastUpdated->format('d.m.Y H:i') }}</p>@endif
            </div>
            <a href="{{ route('champions-league.print') }}" class="btn btn-success mt-2 mt-md-0"><i class="far fa-print" aria-hidden="true"></i> Skriv ut oversikt</a>
        </div>
        @if(!$apiConfigured)<p class="cl-empty mt-3">Champions League-sesongen er ikke koblet til en sikker Schibsted-sesong-ID ennå.</p>
        @elseif($apiError && !$usingStaleData)<p class="cl-empty mt-3">Vi klarer ikke hente oppdaterte Champions League-data akkurat nå. Prøv igjen senere.</p>
        @elseif($apiError)<p class="cl-empty mt-3">Nye data kunne ikke hentes akkurat nå. Viser sist lagrede data.</p>@endif
    </section>

    <section class="cl-section p-3 p-md-4 mb-3">
        <h2 class="h4 mb-1">Tabell / ligafase</h2>
        <p class="cl-muted mb-3">{{ $standingsGroups[0]['stageName'] ?? 'Samlet ligatabell' }}</p>
        @if(count($standings))
        <div class="table-responsive"><table class="table table-sm table-bordered cl-table mb-0"><thead><tr><th>Plass</th><th>Lag</th><th class="text-center">K</th><th class="text-center">V</th><th class="text-center">U</th><th class="text-center">T</th><th class="text-center">Mål</th><th class="text-center">MF</th><th class="text-center">P</th></tr></thead><tbody>
        @foreach($standings as $team)<tr><td>{{ $team['rank'] ?? '-' }}</td><td><span class="cl-team"><a class="cl-team-link" href="{{ route('champions-league.team', $team['teamId']) }}">@if($team['emblemUrl'])<img class="cl-emblem" src="{{ $team['emblemUrl'] }}" alt="{{ $team['teamName'] }}" onerror="this.remove()">@endif<span>{{ $team['teamName'] }}</span></a></span></td><td class="text-center">{{ $team['played'] ?? '-' }}</td><td class="text-center">{{ $team['wins'] ?? '-' }}</td><td class="text-center">{{ $team['draws'] ?? '-' }}</td><td class="text-center">{{ $team['losses'] ?? '-' }}</td><td class="text-center">{{ $team['goalsFor'] ?? '-' }}-{{ $team['goalsAgainst'] ?? '-' }}</td><td class="text-center">{{ $team['goalDifference'] ?? '-' }}</td><td class="text-center font-weight-bold">{{ $team['points'] ?? '-' }}</td></tr>@endforeach
        </tbody></table></div>
        @else <p class="cl-empty">Tabellen er ikke publisert i datagrunnlaget ennå.</p>@endif
    </section>

    <div class="row"><div class="col-lg-6 mb-3"><section class="cl-section p-3 p-md-4 h-100"><h2 class="h4 mb-1">Kommende kamper</h2><p class="cl-phase mb-3">{{ $showingLeaguePhase ? 'Ligafase' : 'Aktuell fase' }}</p>
        @forelse($upcomingFixturesByDate as $date => $matches)<h3 class="h6 mt-3 mb-2">{{ $date }}</h3>@foreach($matches as $match)<div class="cl-match"><div class="cl-match-row"><div><div class="cl-status">{{ $match['timeLabel'] }}@if($match['round']) · {{ $match['round'] }}@endif</div><div class="cl-match-teams"><span class="cl-match-team">@if($match['homeEmblemUrl'])<img class="cl-match-emblem" src="{{ $match['homeEmblemUrl'] }}" alt="" onerror="this.remove()">@endif{{ $match['homeTeam'] }}</span><span class="cl-match-team">@if($match['awayEmblemUrl'])<img class="cl-match-emblem" src="{{ $match['awayEmblemUrl'] }}" alt="" onerror="this.remove()">@endif{{ $match['awayTeam'] }}</span></div></div><div class="cl-status text-right">{{ $match['statusLabel'] }}</div></div></div>@endforeach
        @empty <p class="cl-empty">Terminlisten er ikke publisert i datagrunnlaget ennå.</p>@endforelse
    </section></div><div class="col-lg-6 mb-3"><section class="cl-section p-3 p-md-4 h-100"><h2 class="h4 mb-3">Siste resultater</h2>
        @forelse($recentResultsByDate as $date => $matches)<h3 class="h6 mt-3 mb-2">{{ $date }}</h3>@foreach($matches as $match)<div class="cl-match"><div class="cl-match-row"><div><div class="cl-status">{{ $match['round'] ?: 'Ferdigspilt' }}</div><div class="cl-match-teams"><span class="cl-match-team">@if($match['homeEmblemUrl'])<img class="cl-match-emblem" src="{{ $match['homeEmblemUrl'] }}" alt="" onerror="this.remove()">@endif{{ $match['homeTeam'] }}</span><span class="cl-match-team">@if($match['awayEmblemUrl'])<img class="cl-match-emblem" src="{{ $match['awayEmblemUrl'] }}" alt="" onerror="this.remove()">@endif{{ $match['awayTeam'] }}</span></div></div><div class="cl-score">{{ $match['homeScore'] ?? '-' }} - {{ $match['awayScore'] ?? '-' }}</div></div></div>@endforeach
        @empty <p class="cl-empty">Det finnes ingen ferdigspilte kamper i den aktuelle fasen ennå.</p>@endforelse
    </section></div></div>

    <section class="cl-section p-3 p-md-4 mb-3"><h2 class="h4 mb-2">Sluttspill</h2><p class="cl-muted">Vises automatisk når SportsNext publiserer kamper merket som sluttspill. Runder og oppgjør er hentet fra kampdataene.</p>
        @if(count($knockoutRounds))
            <div class="cl-bracket">
                @foreach($knockoutRounds as $round)
                    <section class="cl-bracket-round"><h3 class="h6 mb-2">{{ $round['label'] }}</h3>
                        @foreach($round['ties'] as $tie)
                            <div class="cl-tie">
                                @foreach($tie['legs'] as $index => $match)
                                    <div class="cl-leg"><div class="cl-phase">{{ count($tie['legs']) > 1 ? 'Kamp '.($index + 1).' · ' : '' }}{{ $match['dateLabel'] }}</div><div class="cl-match-team">@if($match['homeEmblemUrl'])<img class="cl-match-emblem" src="{{ $match['homeEmblemUrl'] }}" alt="" onerror="this.remove()">@endif{{ $match['homeTeam'] }} {{ $match['homeScore'] ?? '-' }} – {{ $match['awayScore'] ?? '-' }} @if($match['awayEmblemUrl'])<img class="cl-match-emblem" src="{{ $match['awayEmblemUrl'] }}" alt="" onerror="this.remove()">@endif{{ $match['awayTeam'] }}</div>@if($match['homeOvertimeScore'] !== null || $match['awayOvertimeScore'] !== null)<div class="cl-phase">Etter ekstraomg.</div>@elseif($match['homePenaltyScore'] !== null || $match['awayPenaltyScore'] !== null)<div class="cl-phase">Etter straffesparkkonkurranse.</div>@endif</div>
                                @endforeach
                                @if($tie['aggregateAvailable'])<p class="cl-aggregate">Sammenlagt: {{ $tie['homeAggregateScore'] ?? '-' }}–{{ $tie['awayAggregateScore'] ?? '-' }}</p>@endif
                                @if($tie['winnerTeamName'])<p class="cl-aggregate">Går videre: {{ $tie['winnerTeamName'] }}</p>@endif
                            </div>
                        @endforeach
                    </section>
                @endforeach
            </div>
        @else
            <p class="cl-empty">Sluttspillkampene er ikke publisert i datagrunnlaget ennå.</p>
        @endif
    </section>
    @include('partials.footer')
</div></div>
@endsection
