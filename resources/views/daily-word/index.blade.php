@extends('layouts.app')

@section('title', 'Dagens ord | INNSATT.NO')

@section('content')
<div class="container page-container daily-word-page">
    @include('partials.header')

    <main class="daily-word-selection">
        <a class="daily-word-selection__back" href="{{ $prisonHomeUrl }}">← Tilbake til forsiden</a>
        <header class="daily-word-selection__intro">
            <h1>Dagens ord</h1>
            <p>Velg en tekst du vil skrive ut.</p>
        </header>

        @if (count($dailyWords))
            <ul class="daily-word-selection__list">
                @foreach ($dailyWords as $dailyWord)
                    <li>
                        <p>{{ $dailyWord['text'] }}</p>
                        <a class="btn btn-primary" href="{{ route('prison.daily-word.print', ['prison' => app(\App\Services\PrisonContext::class)->prison()['slug'], 'word' => $dailyWord['id'], 'return_to' => route('prison.daily-word.index', ['prison' => app(\App\Services\PrisonContext::class)->prison()['slug']], false)]) }}">Skriv ut</a>
                    </li>
                @endforeach
            </ul>
        @else
            <div class="alert alert-light border" role="status">Det er ingen aktive tekster tilgjengelig for utskrift akkurat nå.</div>
        @endif
    </main>

    @include('partials.footer')
</div>
@endsection
