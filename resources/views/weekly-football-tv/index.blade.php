@extends('layouts.app')

@section('title', 'Ukens fotball på TV | Innsatt.no')

@section('content')
@php
    $matchesByDay = collect($weeklyFootball['matches'])->groupBy(fn (array $match) => $match['startsAt']->format('Y-m-d'));
@endphp
<main class="weekly-football-page container my-4 my-md-5">
    <a class="weekly-football-back" href="{{ route('tv') }}">← Til forsiden</a>
    <header class="weekly-football-header">
        <div>
            <p class="weekly-football-kicker">{{ $weeklyFootball['channelSelection'] }}</p>
            <h1>Denne ukens TV-sendte fotballkamper</h1>
            <p>Uke {{ $weeklyFootball['weekStart']->isoWeek }} · {{ $weeklyFootball['weekStart']->locale('nb')->translatedFormat('j. F') }}–{{ $weeklyFootball['weekEnd']->locale('nb')->translatedFormat('j. F Y') }}</p>
        </div>
        <a class="btn btn-success" href="{{ route('weekly-football-tv.print', ['fengsel' => $prison]) }}"><i class="far fa-print" aria-hidden="true"></i> Skriv ut plakat</a>
    </header>

    <form class="weekly-football-selector" method="get" action="{{ route('weekly-football-tv.index') }}">
        <label for="fengsel">Kanalutvalg</label>
        <select id="fengsel" name="fengsel" onchange="this.form.submit()">
            <option value="ringerike" @selected($prison === 'ringerike')>Ringerike fengsel</option>
            <option value="ilseng" @selected($prison === 'ilseng')>Ilseng fengsel</option>
        </select>
        <button class="btn btn-outline-secondary btn-sm" type="submit">Vis</button>
    </form>

    @if($weeklyFootball['hasSourceFailure'])
        <div class="weekly-football-warning" role="status">
            TV-guiden dekker ikke hele denne uken ennå eller kunne ikke hentes for {{ count($weeklyFootball['unavailableDates']) }} av 7 dager. Listen kan derfor være ufullstendig{{ $weeklyFootball['usingStaleData'] ? '; sist lagrede data vises der de finnes.' : '.' }}
        </div>
    @endif

    @if($weeklyFootball['matches'] === [] && ! $weeklyFootball['hasSourceFailure'])
        <p class="weekly-football-empty">Ingen direktesendte kamper fra de valgte ligaene og turneringene er oppført på TV denne uken.</p>
    @else
        @foreach(range(0, 6) as $offset)
            @php($date = $weeklyFootball['weekStart']->copy()->addDays($offset))
            @php($dayMatches = $matchesByDay->get($date->format('Y-m-d'), collect()))
            <section class="weekly-football-day" aria-labelledby="weekly-day-{{ $date->format('Ymd') }}">
                <h2 id="weekly-day-{{ $date->format('Ymd') }}">{{ ucfirst($date->locale('nb')->translatedFormat('l j. F')) }}</h2>
                @if($dayMatches->isEmpty())
                    <p class="weekly-football-no-matches">Ingen oppført kamp.</p>
                @else
                    <div class="weekly-football-match-list">
                        @foreach($dayMatches as $match)
                            <article class="weekly-football-match">
                                <time datetime="{{ $match['startsAt']->toIso8601String() }}"><strong>{{ $match['startsAt']->format('H.i') }}</strong><span>Sendestart</span></time>
                                <div><strong>{{ $match['name'] }}</strong><span>{{ $match['competition'] }}</span></div>
                                <div class="weekly-football-channels">{{ implode(' · ', $match['channels']) }}</div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        @endforeach
    @endif
    <p class="weekly-football-note">Tidspunktet er TV-sendingens starttid fra VG TV-guide. Avspark vises ikke når det ikke er oppgitt i TV-dataene. Sist kontrollert {{ $weeklyFootball['lastUpdated']->locale('nb')->translatedFormat('j. F Y \k\l. H.i') }}.</p>
</main>
@endsection
