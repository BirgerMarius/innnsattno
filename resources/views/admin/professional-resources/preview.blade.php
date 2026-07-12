@extends('layouts.app')

@section('title', 'Forhåndsvis ressurs')

@section('content')
<div class="container page-container py-4">
    @include('admin.professional-resources.partials.nav')

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Forhåndsvisning</h1>
            <p class="text-muted mb-0">Slik vil ressurskortet fremstå offentlig.</p>
        </div>
        <a href="{{ route('admin.professional-resources.edit', $resource) }}" class="btn btn-primary">Tilbake til redigering</a>
    </div>

    <article class="card professional-resource-card">
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2 mb-2">
                <span class="badge {{ $resource->isPublished() ? 'bg-success' : 'bg-secondary' }}">
                    {{ \App\ProfessionalResource::STATUSES[$resource->status] ?? $resource->status }}
                </span>
                @if ($resource->is_featured)
                    <span class="badge bg-success">Særlig anbefalt</span>
                @endif
                @if ($resource->mediaTypeDisplay())
                    <span class="badge bg-light text-dark border">{{ $resource->mediaTypeDisplay() }}</span>
                @endif
            </div>
            <h2 class="h4">{{ $resource->title }}</h2>
            <p class="professional-resource-comment">{{ $resource->comment ?: 'Ingen kommentar er skrevet ennå.' }}</p>
            <dl class="professional-resource-meta">
                <div>
                    <dt>Kategori</dt>
                    <dd>{{ optional($resource->category)->name }}</dd>
                </div>
                @if ($resource->publisher)
                    <div>
                        <dt>Kilde/utgiver</dt>
                        <dd>{{ $resource->publisher }}</dd>
                    </div>
                @endif
                @if ($resource->publication_year)
                    <div>
                        <dt>Publiseringsår</dt>
                        <dd>{{ $resource->publication_year }}</dd>
                    </div>
                @endif
                @if ($resource->last_checked_at)
                    <div>
                        <dt>Sist kontrollert</dt>
                        <dd>{{ $resource->last_checked_at->format('d.m.Y') }}</dd>
                    </div>
                @endif
            </dl>
            @if ($resource->tags->isNotEmpty())
                <div class="professional-resource-tags mb-3" aria-label="Emneord">
                    @foreach ($resource->tags as $tag)
                        <span class="badge bg-light text-dark border">{{ $tag->name }}</span>
                    @endforeach
                </div>
            @endif
            <a href="{{ $resource->url }}" class="btn btn-primary" target="_blank" rel="noopener noreferrer">Gå til ressurs</a>
        </div>
    </article>
</div>
@endsection
