@extends('layouts.app')

@section('title', 'Fotball på TV | Innsatt.no')

@section('content')
@php
    $matchesByDay = collect($weeklyFootball['matches'])->groupBy(fn (array $match) => $match['startsAt']->format('Y-m-d'));
@endphp
<div class="container page-container">
    @include('partials.header')

<main class="weekly-football-page container my-4 my-md-5">
    <a class="weekly-football-back" href="/{{ $prison }}">← Til forsiden</a>
    <header class="weekly-football-header">
        <div>
            <p class="weekly-football-kicker">{{ $weeklyFootball['channelSelection'] }}</p>
            <h1>{{ $weekOffset ? 'Neste ukes' : 'Denne ukens' }} TV-sendte fotballkamper</h1>
            <p>Uke {{ $weeklyFootball['weekStart']->isoWeek }} · {{ $weeklyFootball['weekStart']->locale('nb')->translatedFormat('j. F') }}–{{ $weeklyFootball['weekEnd']->locale('nb')->translatedFormat('j. F Y') }}</p>
        </div>
    </header>

    <section class="weekly-football-selector" aria-label="Velg fotballoversikt">
        <div class="weekly-football-selector__copy">
            <nav class="weekly-football-selector__choices" aria-label="Velg utvalg">
                <span class="weekly-football-selector__label">Velg utvalg</span>
                <a class="{{ ! $showAll ? 'active' : '' }}" @if(! $showAll) aria-current="true" @endif href="{{ route('prison.weekly-football-tv', ['prison' => $prison, 'uke' => $weekOffset ? 'neste' : 'denne', 'utvalg' => 'standard']) }}">Standard</a>
                <a class="{{ $showAll ? 'active' : '' }}" @if($showAll) aria-current="true" @endif href="{{ route('prison.weekly-football-tv', ['prison' => $prison, 'uke' => $weekOffset ? 'neste' : 'denne', 'utvalg' => 'utvidet']) }}">Utvidet</a>
            </nav>
            <p class="weekly-football-selector__description">{{ $weeklyFootball['selectionDescription'] }}</p>
            <p>Valgt uke: {{ $weeklyFootball['weekStart']->locale('nb')->translatedFormat('j. F') }}–{{ $weeklyFootball['weekEnd']->locale('nb')->translatedFormat('j. F Y') }} · Aktivt utvalg: {{ $weeklyFootball['selectionLabel'] }} · {{ count($weeklyFootball['matches']) + count($weeklyFootball['mondayNightMatches'] ?? []) }} kamper.</p>
            <p class="weekly-football-selector__description">Repriser, høydepunkter og studioprogrammer filtreres bort så langt datagrunnlaget tillater.</p>
        </div>
        <div class="weekly-football-selector__actions">
            <a class="btn btn-success weekly-football-selector__print" data-weekly-football-print href="{{ route('prison.weekly-football-tv.print', ['prison' => $prison, 'uke' => $weekOffset ? 'neste' : 'denne', 'utvalg' => $showAll ? 'utvidet' : 'standard']) }}"><i class="far fa-print" aria-hidden="true"></i> Skriv ut oversikten</a>
        </div>
    </section>

    <nav class="weekly-football-week-tabs" aria-label="Velg uke">
        <a class="{{ $weekOffset === 0 ? 'active' : '' }}" href="{{ route('prison.weekly-football-tv', ['prison' => $prison, 'utvalg' => $showAll ? 'utvidet' : 'standard']) }}">Denne uken</a>
        <a class="{{ $weekOffset === 1 ? 'active' : '' }}" href="{{ route('prison.weekly-football-tv', ['prison' => $prison, 'uke' => 'neste', 'utvalg' => $showAll ? 'utvidet' : 'standard']) }}">Neste uke</a>
    </nav>

    @if($weeklyFootball['tvPending'] ?? false)
        <div class="weekly-football-warning" role="status">TV-guiden for Kongsvinger er under klargjøring fordi kanallisten ikke er mottatt. Andre fengslers kanaler brukes ikke.</div>
    @elseif($weeklyFootball['hasMissingLocalData'])
        <div class="weekly-football-warning" role="status">
            Lokale TV-data mangler for {{ count($weeklyFootball['missingLocalDataDates']) }} av 7 dager. Listen kan derfor være ufullstendig{{ $weeklyFootball['usingStaleData'] ? '; sist lagrede data vises der de finnes.' : '.' }}
        </div>
    @elseif($weeklyFootball['usingStaleData'])
        <div class="weekly-football-warning" role="status">Viser sist lagrede TV-data. Oppdateringen kan være forsinket.</div>
    @endif
    @if($weeklyFootball['hasUnpublishedDates'])
        <div class="weekly-football-warning" role="status">TV-programmet er foreløpig ikke publisert for {{ count($weeklyFootball['unpublishedDates']) }} av 7 dager. Det er ikke det samme som dager uten oppførte kamper.</div>
    @endif

    @if($weeklyFootball['matches'] === [] && ($weeklyFootball['mondayNightMatches'] ?? []) === [] && ! $weeklyFootball['hasMissingLocalData'] && ! $weeklyFootball['hasUnpublishedDates'])
        <p class="weekly-football-empty">Ingen TV-sendte kamper i valgt utvalg er oppført denne uken.</p>
    @else
        @foreach(range(0, 6) as $offset)
            @php($date = $weeklyFootball['weekStart']->copy()->addDays($offset))
            @php($dayMatches = $matchesByDay->get($date->format('Y-m-d'), collect()))
            <section class="weekly-football-day" aria-labelledby="weekly-day-{{ $date->format('Ymd') }}">
                <h2 id="weekly-day-{{ $date->format('Ymd') }}">{{ ucfirst($date->locale('nb')->translatedFormat('l j. F')) }}</h2>
                @if($dayMatches->isEmpty())
                    @if(collect($weeklyFootball['unpublishedDates'])->contains(fn ($unpublishedDate) => $unpublishedDate->isSameDay($date)))
                        <p class="weekly-football-no-matches">TV-programmet er foreløpig ikke publisert.</p>
                    @elseif(collect($weeklyFootball['missingLocalDataDates'])->contains(fn ($missingDate) => $missingDate->isSameDay($date)))
                        <p class="weekly-football-no-matches">Lokale TV-data mangler.</p>
                    @else
                        <p class="weekly-football-no-matches">Ingen oppført kamp.</p>
                    @endif
                @else
                    <div class="weekly-football-match-list">
                        @foreach($dayMatches as $match)
                            <article class="weekly-football-match">
                                <time datetime="{{ $match['startsAt']->toIso8601String() }}"><strong>{{ $match['startsAt']->format('H.i') }}</strong><span>Sendestart</span></time>
                                <div class="weekly-football-teams"><strong>@if($match['homeLogoUrl'])<img src="{{ $match['homeLogoUrl'] }}" alt="" onerror="this.remove()">@endif{{ preg_replace('/\s+–\s+.*/u', '', $match['name']) }} <span aria-hidden="true">–</span> @if($match['awayLogoUrl'])<img src="{{ $match['awayLogoUrl'] }}" alt="" onerror="this.remove()">@endif{{ preg_replace('/^.*\s+–\s+/u', '', $match['name']) }}</strong><span>{{ $match['competition'] }}</span></div>
                                <div class="weekly-football-channels">{{ implode(' · ', $match['channels']) }}<br><span>{{ $match['result'] ? 'Sluttresultat '.$match['result'] : ($match['statusLabel'] ?? $match['broadcastType']) }}</span></div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        @endforeach
        @if(($weeklyFootball['mondayNightMatches'] ?? []) !== [])
            <section class="weekly-football-day weekly-football-night" aria-labelledby="weekly-night-to-monday">
                <h2 id="weekly-night-to-monday">Natt til mandag <small>{{ $weeklyFootball['weekEnd']->copy()->addDay()->locale('nb')->translatedFormat('j. F') }}</small></h2>
                <div class="weekly-football-match-list">
                    @foreach($weeklyFootball['mondayNightMatches'] as $match)
                        <article class="weekly-football-match">
                            <time datetime="{{ $match['startsAt']->toIso8601String() }}"><strong>{{ $match['startsAt']->format('H.i') }}</strong><span>Sendestart</span></time>
                            <div class="weekly-football-teams"><strong>@if($match['homeLogoUrl'])<img src="{{ $match['homeLogoUrl'] }}" alt="" onerror="this.remove()">@endif{{ preg_replace('/\s+–\s+.*/u', '', $match['name']) }} <span aria-hidden="true">–</span> @if($match['awayLogoUrl'])<img src="{{ $match['awayLogoUrl'] }}" alt="" onerror="this.remove()">@endif{{ preg_replace('/^.*\s+–\s+/u', '', $match['name']) }}</strong><span>{{ $match['competition'] }}</span></div>
                            <div class="weekly-football-channels">{{ implode(' · ', $match['channels']) }}<br><span>{{ $match['result'] ? 'Sluttresultat '.$match['result'] : ($match['statusLabel'] ?? $match['broadcastType']) }}</span></div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif
    @endif
    <p class="weekly-football-note">Tidspunktet er TV-sendingens starttid fra VG TV-guide. Kalenderuken inkluderer også natt til mandag frem til kl. 06.00. Avspark vises ikke når det ikke er oppgitt i TV-dataene. TV-data sist oppdatert: {{ $weeklyFootball['lastUpdated'] ? $weeklyFootball['lastUpdated']->locale('nb')->translatedFormat('j. F Y \k\l. H.i') : 'ukjent' }}@if($weeklyFootball['sportsNextLastUpdated']) · Kampstatus sist oppdatert: {{ $weeklyFootball['sportsNextLastUpdated']->locale('nb')->translatedFormat('j. F Y \k\l. H.i') }}@endif.</p>
</main>
    @include('partials.footer')
</div>
@endsection
