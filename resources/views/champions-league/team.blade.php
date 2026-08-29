@extends('layouts.app')

@section('title', $team['teamName'].' | '.$competitionName.' | INNSATT.NO')

@push('styles')
<style>
    .cl-team-page { background:#f6f7f9; min-height:100vh; }
    .cl-team-card { background:#fff; border:1px solid #dde2e8; border-radius:6px; }
    .cl-team-back { display:inline-block; margin-bottom:1rem; }
    .cl-team-header { align-items:center; display:flex; flex-wrap:wrap; gap:1rem; }
    .cl-team-header img { height:58px; object-fit:contain; width:58px; }
    .cl-team-header h1 { margin:0; }
    .cl-team-header p { color:#667085; margin:.2rem 0 0; }
    .cl-team-print { margin-left:auto; }
    .cl-team-summary { display:flex; flex-wrap:wrap; gap:.75rem; margin-top:1rem; }
    .cl-team-summary span { background:#eef2f6; border-radius:4px; padding:.45rem .6rem; }
    .cl-team-match { border-top:1px solid #e6eaf0; padding:.75rem 0; }
    .cl-team-match:first-child { border-top:0; }
    .cl-team-match-header { color:#667085; font-size:.875rem; margin-bottom:.35rem; }
    .cl-team-match-row { align-items:center; display:flex; gap:.5rem; }
    .cl-team-match-row img { height:20px; object-fit:contain; width:20px; }
    .cl-team-match-row strong { color:#173f72; }
    .cl-team-score { font-weight:700; margin-left:auto; white-space:nowrap; }
    .cl-team-empty { background:#f8fafc; border:1px dashed #cbd5e1; border-radius:6px; color:#536173; padding:1rem; }
    @media (max-width:575.98px) { .cl-team-card { border-left:0; border-right:0; border-radius:0; } .cl-team-print { margin-left:0; width:100%; } }
</style>
@endpush

@section('content')
<div class="cl-team-page"><div class="container py-4 py-md-5">
    @include('partials.header')
    <main class="cl-team-card p-3 p-md-4">
        <a class="cl-team-back" href="{{ route($competitionRoute) }}">← Tilbake til {{ $competitionName }}</a>
        <header class="cl-team-header">
            @if($team['emblemUrl'])<img src="{{ $team['emblemUrl'] }}" alt="">@endif
            <div><h1>{{ $competitionName }}<br>{{ $team['teamName'] }}</h1><p>Sesongen {{ $seasonLabel }} · Ligafase</p></div>
            <a class="btn btn-success cl-team-print" href="{{ route($teamPrintRoute, $team['teamId']) }}"><i class="far fa-print" aria-hidden="true"></i> Skriv ut kampoversikt</a>
        </header>

        @if($apiError)<p class="cl-team-empty mt-3">Oppdaterte data kunne ikke lastes akkurat nå{{ $usingStaleData ? '. Viser sist lagrede data.' : '.' }}</p>@endif

        @if(count($teamMatches))
            <div class="cl-team-summary" aria-label="Oppsummering av ligafasen">
                @if(($seasonStats['keyFigures'][0]['value'] ?? null) !== null)<span>Plass i ligafasen: <strong>{{ $seasonStats['keyFigures'][0]['value'] }}</strong></span>@endif
                <span>Spilt: <strong>{{ $seasonStats['keyFigures'][1]['value'] ?? 0 }}</strong></span>
                <span>Seire: <strong>{{ $seasonStats['keyFigures'][2]['value'] ?? 0 }}</strong></span>
                <span>Uavgjort: <strong>{{ $seasonStats['keyFigures'][3]['value'] ?? 0 }}</strong></span>
                <span>Tap: <strong>{{ $seasonStats['keyFigures'][4]['value'] ?? 0 }}</strong></span>
                <span>Mål: <strong>{{ $seasonStats['keyFigures'][5]['value'] ?? 0 }}–{{ $seasonStats['keyFigures'][6]['value'] ?? 0 }}</strong></span>
            </div>
        @endif

        <section class="mt-4"><h2 class="h4">Resultater</h2>
            @forelse($results as $match)
                @include('champions-league.partials.team-match', ['match' => $match, 'teamId' => $team['teamId']])
            @empty <p class="cl-team-empty">Ingen ferdigspilte ligafasekamper ennå.</p>@endforelse
        </section>
        <section class="mt-4"><h2 class="h4">Kommende kamper</h2>
            @forelse($fixtures as $match)
                @include('champions-league.partials.team-match', ['match' => $match, 'teamId' => $team['teamId']])
            @empty <p class="cl-team-empty">Ingen kommende ligafasekamper er publisert ennå.</p>@endforelse
        </section>
        @if(!count($teamMatches))<p class="cl-team-empty mt-4">Dette laget har ingen {{ $competitionName }}-kamper i ligafasen i datagrunnlaget.</p>@endif
    </main>
    @include('partials.footer')
</div></div>
@endsection
