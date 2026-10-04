@extends('layouts.app')

@section('title', 'Fangenytt – Innsatt.no')

@section('content')
    @include('partials.header')

    <main class="container fangenytt-page">
        <section class="fangenytt-intro" aria-labelledby="fangenytt-heading">
            <h1 id="fangenytt-heading">Fangenytt</h1>
            <p class="lead">Fangenytt er et nyhetsmagasin av og for innsatte, utgitt av <a class="fangenytt-source-link" href="https://www.fangeforeningen.no/" target="_blank" rel="noopener noreferrer">Fangeforeningen</a>.</p>
            <p>Her finner du tilgjengelige utgaver for lesing og rask utskrift. Velg en utgave for å åpne PDF-en og bruke nettleserens eller PDF-leserens utskriftsfunksjon.</p>
            <p class="fangenytt-disclaimer">Fangenytt utgis av Fangeforeningen. Innsatt.no er ikke ansvarlig for innholdet i magasinet.</p>
        </section>

        <section aria-labelledby="fangenytt-issues-heading">
            <h2 id="fangenytt-issues-heading" class="fangenytt-issues-heading">Tilgjengelige utgaver</h2>
            <div class="fangenytt-issue-list">
                @foreach ($issues as $issue)
                    <article class="fangenytt-issue-card">
                        <a class="fangenytt-cover-link" href="{{ route('fangenytt.pdf', ['number' => $issue['number']]) }}" target="_blank" rel="noopener noreferrer">
                            @if ($issue['has_cover'])
                                <img class="fangenytt-cover" src="{{ route('fangenytt.cover', ['number' => $issue['number']]) }}" alt="Forside til Fangenytt nr. {{ $issue['number'] }}">
                            @else
                                <span class="fangenytt-cover-placeholder" aria-label="Forside ikke tilgjengelig">Forside<br>ikke tilgjengelig</span>
                            @endif
                        </a>
                        <div class="fangenytt-issue-details">
                            <h3>Fangenytt nr. {{ $issue['number'] }}</h3>
                            @if (!empty($issue['edition']))
                                <p class="fangenytt-issue-edition">Utgave {{ $issue['edition'] }}</p>
                            @endif
                        </div>
                        <a class="btn btn-primary fangenytt-open-button"
                           href="{{ route('fangenytt.pdf', ['number' => $issue['number']]) }}"
                           target="_blank"
                           rel="noopener noreferrer">Åpne / skriv ut<span class="visually-hidden"> Fangenytt nr. {{ $issue['number'] }}</span></a>
                    </article>
                @endforeach
            </div>
        </section>
    </main>

    @include('partials.footer')
@endsection
