@extends('layouts.app')

@section('title', 'Fotball på TV | Innsatt.no')

@section('content')
@php
    $matchesByDay = collect($weeklyFootball['matches'])->groupBy(fn (array $match) => $match['startsAt']->format('Y-m-d'));
@endphp
<main class="weekly-football-page container my-4 my-md-5">
    <a class="weekly-football-back" href="{{ route('tv') }}">← Til forsiden</a>
    <header class="weekly-football-header">
        <div>
            <p class="weekly-football-kicker">{{ $weeklyFootball['channelSelection'] }}</p>
            <h1>{{ $weekOffset ? 'Neste ukes' : 'Denne ukens' }} TV-sendte fotballkamper</h1>
            <p>Uke {{ $weeklyFootball['weekStart']->isoWeek }} · {{ $weeklyFootball['weekStart']->locale('nb')->translatedFormat('j. F') }}–{{ $weeklyFootball['weekEnd']->locale('nb')->translatedFormat('j. F Y') }}</p>
        </div>
        <a class="btn btn-success" href="{{ route('weekly-football-tv.print', ['fengsel' => $prison, 'uke' => $weekOffset ? 'neste' : 'denne']) }}"><i class="far fa-print" aria-hidden="true"></i> Skriv ut plakat</a>
    </header>

    <form class="weekly-football-selector" method="get" action="{{ route('weekly-football-tv.index') }}">
        <label for="fengsel">Kanalutvalg</label>
        <select id="fengsel" name="fengsel" onchange="this.form.submit()">
            <option value="ringerike" @selected($prison === 'ringerike')>Ringerike fengsel</option>
            <option value="ilseng" @selected($prison === 'ilseng')>Ilseng fengsel</option>
        </select>
        <button class="btn btn-outline-secondary btn-sm" type="submit">Vis</button>
    </form>

    <nav class="weekly-football-week-tabs" aria-label="Velg uke">
        <a class="{{ $weekOffset === 0 ? 'active' : '' }}" href="{{ route('weekly-football-tv.index', ['fengsel' => $prison]) }}">Denne uken</a>
        <a class="{{ $weekOffset === 1 ? 'active' : '' }}" href="{{ route('weekly-football-tv.index', ['fengsel' => $prison, 'uke' => 'neste']) }}">Neste uke</a>
    </nav>

    @if($weeklyFootball['hasSourceFailure'])
        <div class="weekly-football-warning" role="status">
            TV-guiden kunne ikke hentes for {{ count($weeklyFootball['sourceFailureDates']) }} av 7 dager. Listen kan derfor være ufullstendig{{ $weeklyFootball['usingStaleData'] ? '; sist lagrede data vises der de finnes.' : '.' }}
        </div>
    @elseif($weeklyFootball['usingStaleData'])
        <div class="weekly-football-warning" role="status">Viser sist lagrede TV-data. Oppdateringen kan være forsinket.</div>
    @endif
    @if($weeklyFootball['hasUnpublishedDates'])
        <div class="weekly-football-warning" role="status">TV-programmet er foreløpig ikke publisert for {{ count($weeklyFootball['unpublishedDates']) }} av 7 dager. Det er ikke det samme som dager uten oppførte kamper.</div>
    @endif

    @if($weeklyFootball['matches'] === [] && ! $weeklyFootball['hasSourceFailure'] && ! $weeklyFootball['hasUnpublishedDates'])
        <p class="weekly-football-empty">Ingen direktesendte kamper fra de valgte ligaene og turneringene er oppført på TV denne uken.</p>
    @else
        @foreach(range(0, 6) as $offset)
            @php($date = $weeklyFootball['weekStart']->copy()->addDays($offset))
            @php($dayMatches = $matchesByDay->get($date->format('Y-m-d'), collect()))
            <section class="weekly-football-day" aria-labelledby="weekly-day-{{ $date->format('Ymd') }}">
                <h2 id="weekly-day-{{ $date->format('Ymd') }}">{{ ucfirst($date->locale('nb')->translatedFormat('l j. F')) }}</h2>
                @if($dayMatches->isEmpty())
                    @if(collect($weeklyFootball['unpublishedDates'])->contains(fn ($unpublishedDate) => $unpublishedDate->isSameDay($date)))
                        <p class="weekly-football-no-matches">TV-programmet er foreløpig ikke publisert.</p>
                    @elseif(collect($weeklyFootball['sourceFailureDates'])->contains(fn ($failedDate) => $failedDate->isSameDay($date)))
                        <p class="weekly-football-no-matches">TV-guiden kunne ikke hentes.</p>
                    @else
                        <p class="weekly-football-no-matches">Ingen oppført kamp.</p>
                    @endif
                @else
                    <div class="weekly-football-match-list">
                        @foreach($dayMatches as $match)
                            <article class="weekly-football-match">
                                <time datetime="{{ $match['startsAt']->toIso8601String() }}"><strong>{{ $match['startsAt']->format('H.i') }}</strong><span>Sendestart</span></time>
                                <div class="weekly-football-teams"><strong>@if($match['homeLogoUrl'])<img src="{{ $match['homeLogoUrl'] }}" alt="" onerror="this.remove()">@endif{{ preg_replace('/\s+–\s+.*/u', '', $match['name']) }} <span aria-hidden="true">–</span> @if($match['awayLogoUrl'])<img src="{{ $match['awayLogoUrl'] }}" alt="" onerror="this.remove()">@endif{{ preg_replace('/^.*\s+–\s+/u', '', $match['name']) }}</strong><span>{{ $match['competition'] }}</span></div>
                                <div class="weekly-football-channels">{{ implode(' · ', $match['channels']) }}</div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        @endforeach
    @endif
    <p class="weekly-football-note">Tidspunktet er TV-sendingens starttid fra VG TV-guide. Avspark vises ikke når det ikke er oppgitt i TV-dataene. Sist oppdatert: {{ $weeklyFootball['lastUpdated'] ? $weeklyFootball['lastUpdated']->locale('nb')->translatedFormat('j. F Y \k\l. H.i') : 'ukjent' }}.</p>
</main>
@endsection
