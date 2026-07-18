@extends('layouts.app')

@section('title', 'Premier League 2026/27 | INNSATT.NO')

@push('styles')
<style>
    .pl-page {
        background: #f6f7f9;
        min-height: 100vh;
    }

    .pl-hero,
    .pl-section {
        background: #fff;
        border: 1px solid #dde2e8;
        border-radius: 6px;
    }

    .pl-beta {
        border: 1px solid #b7c2cf;
        border-radius: 999px;
        color: #445364;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: .02em;
        padding: .15rem .55rem;
        text-transform: uppercase;
    }

    .pl-muted {
        color: #667085;
    }

    .pl-title-row {
        align-items: center;
        display: flex;
        flex-wrap: wrap;
    }

    .pl-title-row .pl-beta {
        margin-left: .5rem;
    }

    .pl-table th {
        background: #eef2f6;
        color: #2d3748;
        font-size: .82rem;
        white-space: nowrap;
    }

    .pl-table td {
        vertical-align: middle;
        white-space: nowrap;
    }

    .pl-team {
        align-items: center;
        display: flex;
        gap: .5rem;
        min-width: 170px;
        white-space: normal;
    }

    .pl-emblem {
        height: 24px;
        object-fit: contain;
        width: 24px;
    }

    .pl-match {
        border-top: 1px solid #e6eaf0;
        padding: .65rem 0;
    }

    .pl-match:first-child {
        border-top: 0;
    }

    .pl-match-teams {
        display: grid;
        gap: .35rem;
    }

    .pl-score {
        font-weight: 700;
        min-width: 3.75rem;
        text-align: right;
    }

    .pl-status {
        color: #667085;
        font-size: .875rem;
    }

    .pl-match-row {
        display: flex;
        justify-content: space-between;
    }

    .pl-match-row > * + * {
        margin-left: 1rem;
    }

    .pl-empty {
        background: #f8fafc;
        border: 1px dashed #cbd5e1;
        border-radius: 6px;
        color: #536173;
        margin-bottom: 0;
        padding: 1rem;
    }

    @media (max-width: 575.98px) {
        .pl-hero,
        .pl-section {
            border-left: 0;
            border-right: 0;
            border-radius: 0;
        }
    }
</style>
@endpush

@section('content')
<div class="pl-page">
    <div class="container py-4 py-md-5">
        @include('partials.header')

        <section class="pl-hero p-3 p-md-4 mb-3">
            <div class="d-flex flex-wrap align-items-start justify-content-between">
                <div>
                    <div class="pl-title-row mb-2">
                        <h1 class="h2 mb-0">Premier League 2026/27</h1>
                        <span class="pl-beta">Beta</span>
                    </div>
                    <p class="lead mb-2">
                        Kampoversikt, siste resultater og tabell hentet fra VG/Schibsted sitt sports-API.
                    </p>
                    @if($lastUpdated)
                        <p class="pl-muted mb-0">Sist oppdatert: {{ $lastUpdated->format('d.m.Y H:i') }}</p>
                    @endif
                </div>
                <a href="{{ route('premier-league.print') }}" class="btn btn-success mt-2 mt-md-0">
                    <i class="far fa-print" aria-hidden="true"></i> Skriv ut ukeoversikt
                </a>
            </div>

            @if(!$apiConfigured)
                <p class="pl-empty mt-3">
                    Premier League-sesongen er ikke koblet til en sikker Schibsted-sesong-ID ennå. Siden er klar til å vise data når riktig ID er satt.
                </p>
            @elseif($apiError && !$usingStaleData)
                <p class="pl-empty mt-3">
                    Vi klarer ikke hente oppdaterte Premier League-data akkurat nå. Prøv igjen senere.
                </p>
            @elseif($apiError && $usingStaleData)
                <p class="pl-empty mt-3">
                    Nye data kunne ikke hentes akkurat nå. Viser sist lagrede data.
                </p>
            @endif
        </section>

        <section class="pl-section p-3 p-md-4 mb-3">
            <h2 class="h4 mb-3">Tabell</h2>

            @if(count($standings) > 0)
                <div class="table-responsive">
                    <table class="table table-sm table-bordered pl-table mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Lag</th>
                                <th class="text-center">S</th>
                                <th class="text-center">V</th>
                                <th class="text-center">U</th>
                                <th class="text-center">T</th>
                                <th class="text-center">Målf.</th>
                                <th class="text-center">P</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($standings as $team)
                                <tr>
                                    <td>{{ $team['rank'] ?? '-' }}</td>
                                    <td>
                                        <span class="pl-team">
                                            @if(!empty($team['emblemUrl']))
                                                <img class="pl-emblem" src="{{ $team['emblemUrl'] }}" alt="">
                                            @endif
                                            <span>{{ $team['teamName'] }}</span>
                                        </span>
                                    </td>
                                    <td class="text-center">{{ $team['played'] ?? '-' }}</td>
                                    <td class="text-center">{{ $team['wins'] ?? '-' }}</td>
                                    <td class="text-center">{{ $team['draws'] ?? '-' }}</td>
                                    <td class="text-center">{{ $team['losses'] ?? '-' }}</td>
                                    <td class="text-center">{{ $team['goalDifference'] ?? '-' }}</td>
                                    <td class="text-center font-weight-bold">{{ $team['points'] ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="pl-empty">Tabellen er ikke publisert i datagrunnlaget ennå.</p>
            @endif
        </section>

        <div class="row">
            <div class="col-lg-6 mb-3">
                <section class="pl-section p-3 p-md-4 h-100">
                    <h2 class="h4 mb-3">Kommende kamper</h2>

                    @if(count($upcomingFixtures) > 0)
                        @foreach($upcomingFixturesByDate as $date => $matches)
                            <h3 class="h6 mt-3 mb-2">{{ $date }}</h3>
                            @foreach($matches as $match)
                                <div class="pl-match">
                                    <div class="pl-match-row">
                                        <div>
                                            <div class="pl-status">
                                                {{ $match['timeLabel'] }}
                                                @if($match['round'])
                                                    · {{ $match['round'] }}
                                                @endif
                                            </div>
                                            <div class="pl-match-teams">
                                                <span>{{ $match['homeTeam'] }}</span>
                                                <span>{{ $match['awayTeam'] }}</span>
                                            </div>
                                        </div>
                                        <div class="pl-status text-right">{{ $match['statusLabel'] }}</div>
                                    </div>
                                </div>
                            @endforeach
                        @endforeach
                    @else
                        <p class="pl-empty">Terminlisten er ikke publisert i datagrunnlaget ennå.</p>
                    @endif
                </section>
            </div>

            <div class="col-lg-6 mb-3">
                <section class="pl-section p-3 p-md-4 h-100">
                    <h2 class="h4 mb-3">Siste resultater</h2>

                    @if(count($recentResults) > 0)
                        @foreach($recentResultsByDate as $date => $matches)
                            <h3 class="h6 mt-3 mb-2">{{ $date }}</h3>
                            @foreach($matches as $match)
                                <div class="pl-match">
                                    <div class="pl-match-row">
                                        <div>
                                            <div class="pl-status">
                                                @if($match['round'])
                                                    {{ $match['round'] }}
                                                @else
                                                    Ferdigspilt
                                                @endif
                                            </div>
                                            <div class="pl-match-teams">
                                                <span>{{ $match['homeTeam'] }}</span>
                                                <span>{{ $match['awayTeam'] }}</span>
                                            </div>
                                        </div>
                                        <div class="pl-score">
                                            {{ $match['homeScore'] ?? '-' }} - {{ $match['awayScore'] ?? '-' }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @endforeach
                    @else
                        <p class="pl-empty">Det finnes ingen ferdigspilte Premier League-kamper i datagrunnlaget ennå.</p>
                    @endif
                </section>
            </div>
        </div>

        @include('partials.footer')
    </div>
</div>
@endsection
