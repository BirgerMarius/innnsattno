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

        <section class="football-team-season-stats" aria-labelledby="season-stats-heading">
            <h2 id="season-stats-heading">Sesongen så langt</h2>
            @if($seasonStats['hasFinishedMatches'])
                <div class="football-team-stat-grid">
                    @foreach($seasonStats['keyFigures'] as $stat)
                        <div class="football-team-stat-card"><span>{{ $stat['label'] }}</span><strong>{{ $stat['value'] ?? '–' }}</strong></div>
                    @endforeach
                </div>

                <div class="football-team-season-details">
                    <div>
                        <h3>Form siste {{ count($seasonStats['form']) }}</h3>
                        <div class="football-team-form" aria-label="Form: {{ implode(', ', array_column($seasonStats['form'], 'result')) }}">
                            @foreach($seasonStats['form'] as $formMatch)
                                <span class="football-team-form-marker football-team-form-marker--{{ strtolower($formMatch['result']) }}">{{ $formMatch['result'] }}</span>
                            @endforeach
                        </div>
                    </div>
                    <div class="football-team-home-away">
                        <h3>Hjemme og borte</h3>
                        <p><strong>Hjemme:</strong> {{ $seasonStats['home']['wins'] }}–{{ $seasonStats['home']['draws'] }}–{{ $seasonStats['home']['losses'] }}</p>
                        <p><strong>Borte:</strong> {{ $seasonStats['away']['wins'] }}–{{ $seasonStats['away']['draws'] }}–{{ $seasonStats['away']['losses'] }}</p>
                    </div>
                </div>

                <div class="football-team-records">
                    <h3>Sesongrekorder</h3>
                    <dl>
                        <div><dt>Største seier</dt><dd>@if($seasonStats['records']['largestWin']){{ $seasonStats['records']['largestWin']['teamScore'] }}–{{ $seasonStats['records']['largestWin']['opponentScore'] }} mot {{ $seasonStats['records']['largestWin']['opponent'] }}@else–@endif</dd></div>
                        <div><dt>Største tap</dt><dd>@if($seasonStats['records']['largestLoss']){{ $seasonStats['records']['largestLoss']['teamScore'] }}–{{ $seasonStats['records']['largestLoss']['opponentScore'] }} mot {{ $seasonStats['records']['largestLoss']['opponent'] }}@else–@endif</dd></div>
                        <div><dt>Mest målrike kamp</dt><dd>@if($seasonStats['records']['highestScoringMatch']){{ $seasonStats['records']['highestScoringMatch']['teamScore'] }}–{{ $seasonStats['records']['highestScoringMatch']['opponentScore'] }} mot {{ $seasonStats['records']['highestScoringMatch']['opponent'] }}@else–@endif</dd></div>
                        <div><dt>Clean sheets</dt><dd>{{ $seasonStats['records']['cleanSheets'] }}</dd></div>
                    </dl>
                </div>
            @else
                <p class="football-team-warning">Det finnes ingen ferdigspilte seriekamper å beregne sesongstatistikk fra ennå.</p>
            @endif
        </section>

        <p class="football-team-note"><strong>Merk:</strong> Dato og tidspunkt for kommende kamper kan endres. Kampoversikten oppdateres når nye opplysninger blir tilgjengelige.</p>
    </main>

    @include('partials.footer')
</div>
@endsection
