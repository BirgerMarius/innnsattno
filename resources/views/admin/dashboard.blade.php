@extends('layouts.app')

@section('title', 'Administrasjon - Anbefalt fagstoff')

@section('content')
<div class="container page-container admin-area py-4">
    @include('admin.professional-resources.partials.nav')

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Anbefalt fagstoff</h1>
            <p class="text-muted mb-0">Oversikt over ressurser og kategorier.</p>
        </div>
        <a href="{{ route('professional-resources.index') }}" class="btn btn-outline-primary">Offentlig side</a>
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

    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('admin.professional-resources.index') }}" class="btn btn-primary">Ressurser</a>
        <a href="{{ route('admin.resource-categories.index') }}" class="btn btn-primary">Kategorier</a>
        <a href="{{ route('admin.professional-resources.create') }}" class="btn btn-success">Ny ressurs</a>
        <a href="{{ route('admin.resource-categories.create') }}" class="btn btn-success">Ny kategori</a>
    </div>
</div>
@endsection
