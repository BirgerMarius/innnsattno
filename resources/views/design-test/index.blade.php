@extends('layouts.app')

@section('title', 'Designforhåndsvisninger – Innsatt.no')
@section('body-class', 'design-test-overview')

@section('content')
    <main class="container design-test-shell">
        <a href="{{ route('tv') }}" class="design-test-back">← Til ordinær forside</a>
        <header class="design-test-heading">
            <p>UTVIKLING / FORHÅNDSVISNING</p>
            <h1>Sesongtemaer for Innsatt.no</h1>
            <span>Alle variantene bruker den samme forsiden. Bare farger, overflater og små dekorasjoner varierer.</span>
        </header>

        <section class="design-test-grid" aria-label="Velg designvariant">
            @foreach ($themes as $theme)
                <a class="design-test-card front-page-theme--{{ $theme['id'] }}" href="{{ route('design-test.preview', $theme['id']) }}">
                    <span class="design-test-card-swatch" aria-hidden="true"></span>
                    <h2>{{ $theme['name'] }}</h2>
                    <p>{{ $theme['description'] }}</p>
                    <span>Åpne forhåndsvisning →</span>
                </a>
            @endforeach
        </section>
    </main>
@endsection
