@extends('layouts.app')

@section('title', 'Eliteserien API-test | INNSATT.NO')

@section('content')
<div class="container my-5">
    @include('partials.header')

    <h1>Eliteserien API-test</h1>

    <p>Denne siden viser teknisk status for Eliteserien-koblingen uten API-nøkler eller sensitive headere.</p>

    <table class="table table-bordered table-sm">
        <tbody>
            <tr>
                <th>Schibsted turnerings-ID</th>
                <td>{{ $tournamentId ?: 'Ikke konfigurert' }}</td>
            </tr>
            <tr>
                <th>Schibsted sesong-ID</th>
                <td>{{ $seasonId ?: 'Ikke konfigurert' }}</td>
            </tr>
            <tr>
                <th>API konfigurert</th>
                <td>{{ $apiConfigured ? 'Ja' : 'Nei' }}</td>
            </tr>
            <tr>
                <th>API-feil ved siste forsøk</th>
                <td>{{ $apiError ? 'Ja' : 'Nei' }}</td>
            </tr>
            <tr>
                <th>Viser cachede data</th>
                <td>{{ $usingStaleData ? 'Ja' : 'Nei' }}</td>
            </tr>
            <tr>
                <th>Tabellrader</th>
                <td>{{ $standingCount }}</td>
            </tr>
            <tr>
                <th>Kommende kamper</th>
                <td>{{ $fixtureCount }}</td>
            </tr>
            <tr>
                <th>Siste resultater</th>
                <td>{{ $resultCount }}</td>
            </tr>
            <tr>
                <th>Sist oppdatert</th>
                <td>{{ $lastUpdated ? $lastUpdated->format('d.m.Y H:i') : 'Ikke oppgitt av API-et' }}</td>
            </tr>
        </tbody>
    </table>

    @if(count($endpoints) > 0)
        <h2 class="h4">Endepunkter</h2>
        <ul>
            @foreach($endpoints as $name => $url)
                <li><strong>{{ $name }}:</strong> {{ $url }}</li>
            @endforeach
        </ul>
    @else
        <p class="alert alert-info">Sett riktig Schibsted-sesong-ID før endepunktene kan testes.</p>
    @endif

    @include('partials.footer')
</div>
@endsection
