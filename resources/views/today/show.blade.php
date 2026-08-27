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

            @if ($mourningFlagging)
                <section class="today-card today-mourning-flagging">
                    <h2>{{ $mourningFlagging['title'] }}</h2>
                    <p class="today-large-text">{{ $mourningFlagging['message'] }}</p>
                    @if ($mourningFlagging['source_url'])
                        <a class="today-external-link" href="{{ $mourningFlagging['source_url'] }}" target="_blank" rel="noopener noreferrer">Offisiell informasjon: {{ $mourningFlagging['source_name'] }} <span aria-hidden="true">↗</span><span class="visually-hidden"> (åpnes i ny fane)</span></a>
                    @endif
                </section>
            @endif

            @if ($history['observance'])
                <section class="today-card today-observance">
                    <p class="today-card-label">Dagens merkedag</p>
                    <h2>{{ $history['observance']['title'] }}</h2>
                    <p>{{ $history['observance']['description'] }}</p>
                    <a class="today-external-link" href="{{ $history['observance']['url'] }}" target="_blank" rel="noopener noreferrer">Kilde: {{ $history['observance']['source_name'] }} <span aria-hidden="true">↗</span><span class="visually-hidden"> (åpnes i ny fane)</span></a>
                </section>
            @endif
        </div>

        @if ($history['norway_events'])
        <section class="today-section today-section-primary" aria-labelledby="norway-events-heading">
            <p class="today-section-label">Norsk historie</p>
            <h2 id="norway-events-heading">Denne dagen i Norge</h2>
                <ol class="today-timeline">
                    @foreach ($history['norway_events'] as $item)
                        <li>
                            <time>{{ $item['year'] }}</time>
                            <div>
                                <h3>{{ $item['title'] }}</h3>
                                <p>{{ $item['description'] }}</p>
                                @if ($item['url'])
                                    <a class="today-external-link" href="{{ $item['url'] }}" target="_blank" rel="noopener noreferrer">Kilde: {{ $item['source_name'] }} <span aria-hidden="true">↗</span><span class="visually-hidden"> (åpnes i ny fane)</span></a>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ol>
        </section>
        @endif

        @if ($history['world_events'])
            <section class="today-section today-section-world" aria-labelledby="world-events-heading">
                <p class="today-section-label">Store hendelser</p>
                <h2 id="world-events-heading">Denne dagen i verden</h2>
                <ol class="today-timeline">
                    @foreach ($history['world_events'] as $item)
                        <li><time>{{ $item['year'] }}</time><div><h3>{{ $item['title'] }}</h3><p>{{ $item['description'] }}</p>@if ($item['url'])<a class="today-external-link" href="{{ $item['url'] }}" target="_blank" rel="noopener noreferrer">Kilde: {{ $item['source_name'] }} <span aria-hidden="true">↗</span><span class="visually-hidden"> (åpnes i ny fane)</span></a>@endif</div></li>
                    @endforeach
                </ol>
            </section>
        @endif

        <div class="today-people-grid">
            @foreach (['births' => 'Født denne dagen', 'deaths' => 'Døde denne dagen'] as $type => $heading)
                @if ($history[$type])
                <section class="today-section">
                    <h2>{{ $heading }}</h2>
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
                                        <small>Kilde: {{ $item['source_name'] }}</small>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                </section>
                @endif
            @endforeach
        </div>

        @if (!$history['norway_events'] && !$history['world_events'] && !$history['births'] && !$history['deaths'])
            <p class="today-history-empty">Vi fant ingen historiske oppføringer med god nok norsk tekst for denne datoen.</p>
        @endif

        @if ($history['fact'])
            <aside class="today-fact" aria-labelledby="today-fact-heading">
                <p class="today-card-label">Visste du at?</p>
                <h2 id="today-fact-heading">{{ $history['fact']['title'] }}</h2>
                <p>{{ $history['fact']['description'] }}</p>
                <a class="today-external-link" href="{{ $history['fact']['url'] }}" target="_blank" rel="noopener noreferrer">Kilde: {{ $history['fact']['source_name'] }} <span aria-hidden="true">↗</span><span class="visually-hidden"> (åpnes i ny fane)</span></a>
            </aside>
        @endif

        <aside class="today-sources" aria-labelledby="sources-heading">
            <h2 id="sources-heading">Kilder og lisens</h2>
            <p>Navnedager hentes fra <a href="https://webapi.no/" target="_blank" rel="noopener noreferrer">Webapi.no ↗</a>. Historiske hendelser, fødsler og dødsfall hentes automatisk fra Wikimedia og kvalitetssjekkes mot Wikidata. Norske beskrivelser og norske artikler prioriteres.</p>
            <p>Tekst fra Wikipedia er tilgjengelig under <a href="https://creativecommons.org/licenses/by-sa/4.0/deed.no" target="_blank" rel="noopener noreferrer">Creative Commons Navngivelse-DelPåSammeVilkår 4.0 ↗</a>. Wikipedia er et registrert varemerke for Wikimedia Foundation. Opplysningene kan inneholde feil og bør kontrolleres mot lenkede artikler.</p>
        </aside>

        @include('partials.footer')
    </div>
</main>
@endsection
