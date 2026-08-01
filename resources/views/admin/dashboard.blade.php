@extends('layouts.app')

@section('title', 'Administrasjon')

@section('content')
<div class="container page-container admin-area py-4">
    @include('admin.partials.nav')

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Administrasjon</h1>
            <p class="text-muted mb-0">Oversikt over innhold som trenger oppfølging.</p>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card admin-dashboard-card h-100">
                <div class="card-body">
                    <div class="text-muted small">Publisert</div>
                    <div class="admin-dashboard-number">{{ $publishedCount }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card admin-dashboard-card h-100">
                <div class="card-body">
                    <div class="text-muted small">Kladd</div>
                    <div class="admin-dashboard-number">{{ $draftCount }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card admin-dashboard-card h-100">
                <div class="card-body">
                    <div class="text-muted small">Aktive kategorier</div>
                    <div class="admin-dashboard-number">{{ $activeCategoryCount }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <section class="card admin-dashboard-section h-100">
                <div class="card-body d-flex flex-column">
                    <h2 class="h5">Fagstoff</h2>
                    <p class="text-muted">{{ $draftCount }} kladder og {{ $activeCategoryCount }} aktive kategorier.</p>
                    <div class="d-flex flex-wrap gap-2 mt-auto">
                        <a href="{{ route('admin.professional-resources.index') }}" class="btn btn-primary">Se ressurser</a>
                        <a href="{{ route('admin.professional-resources.create') }}" class="btn btn-outline-primary">Ny ressurs</a>
                    </div>
                </div>
            </section>
        </div>
        <div class="col-lg-4">
            <section class="card admin-dashboard-section h-100">
                <div class="card-body d-flex flex-column">
                    <h2 class="h5">Nyheter</h2>
                    <p class="text-muted">{{ $pendingNewsCount }} nye artikler venter på vurdering. {{ $activeNewsSourceCount }} kilder er aktive.</p>
                    <div class="d-flex flex-wrap gap-2 mt-auto">
                        <a href="{{ route('admin.news.index', ['status' => 'pending']) }}" class="btn btn-primary">Vurder artikler</a>
                        <a href="{{ route('admin.news-sources.index') }}" class="btn btn-outline-primary">Se kilder</a>
                    </div>
                </div>
            </section>
        </div>
        <div class="col-lg-4">
            <section class="card admin-dashboard-section h-100">
                <div class="card-body d-flex flex-column">
                    <h2 class="h5">Forslag</h2>
                    <p class="text-muted">{{ $newFeedbackCount }} nye forslag eller tilbakemeldinger.</p>
                    <div class="mt-auto">
                        <a href="{{ route('admin.feedback.index', ['status' => 'new']) }}" class="btn btn-primary">Se nye forslag</a>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <section class="card admin-dashboard-section mt-3">
        <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h2 class="h5 mb-1">Statistikk og serverlogg</h2>
                <p class="text-muted mb-0">Åpne serverens rapport for trafikk og besøksstatistikk.</p>
            </div>
            <a href="https://innsatt.no/statistikk/" class="btn btn-outline-primary">Åpne rapport</a>
        </div>
    </section>
</div>
@endsection
