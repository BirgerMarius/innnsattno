@extends('layouts.app')

@section('title', 'Fangenytt – Innsatt.no')

@section('content')
    @include('partials.header')

    <main class="container fangenytt-page">
        <section class="fangenytt-intro" aria-labelledby="fangenytt-heading">
            <h1 id="fangenytt-heading">Fangenytt</h1>
            <p class="lead">Fangenytt er et nyhetsmagasin av og for innsatte, utgitt av Fangeforeningen.</p>
            <p>Her finner du tilgjengelige utgaver for lesing og rask utskrift. Velg en utgave for å åpne original-PDF-en og bruke nettleserens eller PDF-leserens utskriftsfunksjon.</p>
            <p class="fangenytt-disclaimer">Fangenytt utgis av Fangeforeningen. Innsatt.no er ikke ansvarlig for innholdet i magasinet.</p>
        </section>

        <section aria-labelledby="fangenytt-issues-heading">
            <h2 id="fangenytt-issues-heading" class="fangenytt-issues-heading">Tilgjengelige utgaver</h2>
            <div class="fangenytt-issue-list">
                @foreach ($issues as $issue)
                    <article class="fangenytt-issue-card">
                        <div>
                            <h3>Fangenytt nr. {{ $issue['number'] }}</h3>
                            @if (!empty($issue['edition']))
                                <p class="fangenytt-issue-edition">Utgave {{ $issue['edition'] }}</p>
                            @endif
                        </div>
                        <a class="btn btn-primary fangenytt-open-button"
                           href="{{ $issue['url'] }}"
                           target="_blank"
                           rel="noopener noreferrer">Åpne / skriv ut<span class="visually-hidden"> Fangenytt nr. {{ $issue['number'] }}</span></a>
                    </article>
                @endforeach
            </div>
        </section>
    </main>

    @include('partials.footer')
@endsection
