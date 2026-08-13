@extends('layouts.app')

@section('title', $team['teamName'].' | '.$leagueName.' | INNSATT.NO')

@section('content')
<div class="container page-container football-team-page">
    @include('partials.header')

    <main class="football-team-shell">
        <a class="football-team-back" href="{{ route($backRoute) }}">← Tilbake til {{ $leagueName }}</a>
        <header class="football-team-header">
            @if($team['emblemUrl'])
                <img class="football-team-emblem" src="{{ $team['emblemUrl'] }}" alt="" onerror="this.remove()">
            @endif
            <div>
                <h1>{{ $team['teamName'] }}</h1>
                <p>{{ $leagueName }}@if($seasonName) · {{ $seasonName }}@endif</p>
                <p class="football-team-subtitle">Alle seriekamper</p>
            </div>
            <a href="{{ route($printRoute, $team['teamId']) }}" class="btn btn-success football-team-print"><i class="far fa-print" aria-hidden="true"></i> Skriv ut kampoversikt</a>
        </header>

        @if($apiError)
            <p class="football-team-warning">Oppdaterte data kunne ikke lastes akkurat nå{{ $usingStaleData ? '. Viser sist lagrede data.' : '.' }}</p>
        @endif

        @if(count($teamMatches))
            <div class="football-team-list" aria-label="Alle kamper for {{ $team['teamName'] }}">
                @foreach($teamMatches as $match)
                    <article class="football-team-match">
                        <div class="football-team-date">{{ $match['startsAt'] ? $match['startsAt']->format('d.m.Y') : 'Dato ikke satt' }}@if($match['timeLabel'])<span>{{ $match['timeLabel'] }}</span>@endif</div>
                        <div class="football-team-opponents"><strong>{{ $match['homeTeam'] }}</strong><span class="football-team-score">@if($match['isFinished']){{ $match['homeScore'] ?? '–' }}–{{ $match['awayScore'] ?? '–' }}@else – @endif</span><strong>{{ $match['awayTeam'] }}</strong></div>
                        <div class="football-team-meta">{{ $match['isHome'] ? 'Hjemme' : 'Borte' }} · {{ $match['statusLabel'] }}@if($match['round']) · {{ $match['round'] }}@endif</div>
                    </article>
                @endforeach
            </div>
        @else
            <p class="football-team-warning">Ingen seriekamper for dette laget finnes i datagrunnlaget ennå.</p>
        @endif

        <p class="football-team-note"><strong>Merk:</strong> Dato og tidspunkt for kommende kamper kan endres. Kampoversikten oppdateres når nye opplysninger blir tilgjengelige.</p>
    </main>

    @include('partials.footer')
</div>
@endsection
