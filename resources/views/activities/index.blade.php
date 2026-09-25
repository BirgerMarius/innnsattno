@extends('layouts.app')

@section('title', 'Spillforslag | INNSATT.NO')

@section('content')
<div class="container page-container activity-page">
    @include('partials.header')
    <main class="activity-shell">
        <header class="activity-intro"><p class="activity-kicker">Aktivisering uten internett</p><h1>Spillforslag</h1><p>Enkle spill som kan settes i gang med kort eller brett. Velg et spill for regler på skjerm eller et ferdig utskriftsark.</p></header>
        <div class="activity-grid">
            @foreach($activities as $slug => $item)
                <a class="activity-card" href="{{ route('activities.show', $slug) }}"><span class="activity-card-kind">{{ $item['kind'] }}</span><h2>{{ $item['title'] }}</h2><p>{{ $item['players'] }}</p><span>Se regler og skriv ut <span aria-hidden="true">→</span></span></a>
            @endforeach
        </div>
    </main>
    @include('partials.footer')
</div>
@endsection
