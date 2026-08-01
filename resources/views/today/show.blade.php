@extends('layouts.app')

@section('title', 'Dagen i dag – innsatt.no')

@section('content')
<main class="today-page">
    <div class="container py-4 py-md-5">
        @include('partials.header')

        <nav class="today-navigation" aria-label="Velg dato">
            <a class="btn btn-outline-primary" href="{{ route('today.show', ['date' => $date->subDay()->toDateString()]) }}">← Forrige dag</a>
            @unless ($date->isSameDay($today))
                <a class="btn btn-outline-secondary" href="{{ route('today.show') }}">I dag</a>
            @endunless
            <a class="btn btn-outline-primary" href="{{ route('today.show', ['date' => $date->addDay()->toDateString()]) }}">Neste dag →</a>
        </nav>

        <header class="today-hero">
            <p class="today-kicker">Dagen i dag</p>
            <h1>{{ ucfirst($date->locale('nb')->translatedFormat('l j. F Y')) }}</h1>
            <div class="today-facts" aria-label="Kalenderopplysninger">
                <span><strong>Uke</strong> {{ $date->isoWeek }}</span>
                <span><strong>Dag</strong> {{ $date->dayOfYear }} av {{ $date->daysInYear }}</span>
                <span><strong>Igjen av året</strong> {{ $date->daysInYear - $date->dayOfYear }}</span>
            </div>
        </header>

        <div class="today-highlight-grid">
            <section class="today-card">
                <h2>Navnedager</h2>
                @if ($namedays)
                    <p class="today-large-text">{{ implode(' og ', $namedays) }}</p>
                @else
                    <p class="text-muted mb-0">Navnedager er ikke tilgjengelige akkurat nå.</p>
                @endif
            </section>

            <section class="today-card">
                <h2>Flaggdag</h2>
                @if ($flagDay)
                    <p class="today-large-text">🇳🇴 {{ $flagDay['name'] }}@isset($flagDay['age']) ({{ $flagDay['age'] }} år)@endisset</p>
                    @if ($flagDay['information_url'])
                        <a class="today-external-link" href="{{ $flagDay['information_url'] }}" target="_blank" rel="noopener noreferrer">Les mer <span aria-hidden="true">↗</span><span class="visually-hidden"> (åpnes i ny fane)</span></a>
                    @endif
                @else
                    <p class="text-muted mb-0">Dette er ikke en offisiell norsk flaggdag.</p>
                @endif
            </section>
        </div>

        <section class="today-section" aria-labelledby="events-heading">
            <h2 id="events-heading">Historiske hendelser</h2>
            @if ($history['events'])
                <ol class="today-timeline">
                    @foreach ($history['events'] as $item)
                        <li>
                            <time>{{ $item['year'] }}</time>
                            <div>
                                <p>{{ $item['text'] }}</p>
                                @if ($item['url'])
                                    <a class="today-external-link" href="{{ $item['url'] }}" target="_blank" rel="noopener noreferrer">{{ $item['has_norwegian_page'] ? 'Norsk Wikipedia' : 'Wikipedia (engelsk)' }} <span aria-hidden="true">↗</span><span class="visually-hidden"> (åpnes i ny fane)</span></a>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ol>
            @else
                <p class="today-unavailable">Historiske hendelser er ikke tilgjengelige akkurat nå.</p>
            @endif
        </section>

        <div class="today-people-grid">
            @foreach (['births' => 'Født denne dagen', 'deaths' => 'Døde denne dagen'] as $type => $heading)
                <section class="today-section">
                    <h2>{{ $heading }}</h2>
                    @if ($history[$type])
                        <ul class="today-people-list">
                            @foreach ($history[$type] as $item)
                                <li>
                                    <span class="today-person-year">{{ $item['year'] }}</span>
                                    <div>
                                        @if ($item['url'])
                                            <a href="{{ $item['url'] }}" target="_blank" rel="noopener noreferrer">{{ $item['title'] }} <span aria-hidden="true">↗</span><span class="visually-hidden"> (åpnes i ny fane)</span></a>
                                        @else
                                            <strong>{{ $item['title'] }}</strong>
                                        @endif
                                        @if ($item['description'])<small>{{ $item['description'] }}</small>@endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="today-unavailable">Opplysninger er ikke tilgjengelige akkurat nå.</p>
                    @endif
                </section>
            @endforeach
        </div>

        <aside class="today-sources" aria-labelledby="sources-heading">
            <h2 id="sources-heading">Kilder og lisens</h2>
            <p>Navnedager hentes fra <a href="https://webapi.no/" target="_blank" rel="noopener noreferrer">Webapi.no ↗</a>. Historiske hendelser, fødsler og dødsfall hentes fra Wikimedia sin «On this day»-tjeneste og lenker til Wikipedia.</p>
            <p>Tekst fra Wikipedia er tilgjengelig under <a href="https://creativecommons.org/licenses/by-sa/4.0/deed.no" target="_blank" rel="noopener noreferrer">Creative Commons Navngivelse-DelPåSammeVilkår 4.0 ↗</a>. Wikipedia er et registrert varemerke for Wikimedia Foundation. Opplysningene kan inneholde feil og bør kontrolleres mot lenkede artikler.</p>
        </aside>

        @include('partials.footer')
    </div>
</main>
@endsection
