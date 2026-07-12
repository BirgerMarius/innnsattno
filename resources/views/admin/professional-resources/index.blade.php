@extends('layouts.app')

@section('title', 'Ressurser - Anbefalt fagstoff')

@section('content')
<div class="container page-container admin-professional-resources py-4">
    @include('admin.professional-resources.partials.nav')

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Ressurser</h1>
            <p class="text-muted mb-0">Opprett, vurder og publiser fagressurser.</p>
        </div>
        <a href="{{ route('admin.professional-resources.create') }}" class="btn btn-success">Ny ressurs</a>
    </div>

    @include('admin.professional-resources.partials.messages')

    <div class="d-flex flex-wrap gap-2 mb-4">
        <a href="{{ route('admin.professional-resources.index') }}" class="btn {{ $activeStatus === null ? 'btn-primary' : 'btn-outline-secondary' }}">
            Alle <span class="badge bg-light text-dark">{{ $allStatusCount }}</span>
        </a>
        @foreach ($statuses as $value => $label)
            <a href="{{ route('admin.professional-resources.index', ['status' => $value]) }}" class="btn {{ $activeStatus === $value ? 'btn-primary' : 'btn-outline-secondary' }}">
                {{ $label }} <span class="badge bg-light text-dark">{{ $statusCounts[$value] ?? 0 }}</span>
            </a>
        @endforeach
    </div>

    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <caption class="visually-hidden">Ressurser</caption>
            <thead>
                <tr>
                    <th scope="col">Tittel</th>
                    <th scope="col">Kategori</th>
                    <th scope="col">Medietype</th>
                    <th scope="col">Status</th>
                    <th scope="col">Sortering</th>
                    <th scope="col">Handlinger</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($resources as $resource)
                    <tr>
                        <td>
                            <strong>{{ $resource->title }}</strong>
                            @if ($resource->is_featured)
                                <span class="badge bg-success ms-1">Særlig anbefalt</span>
                            @endif
                        </td>
                        <td>{{ optional($resource->category)->name }}</td>
                        <td>{{ $resource->mediaTypeDisplay() ?: 'Ikke valgt' }}</td>
                        <td>
                            <span class="badge {{ $resource->isPublished() ? 'bg-success' : 'bg-secondary' }}">
                                {{ $statuses[$resource->status] ?? $resource->status }}
                            </span>
                        </td>
                        <td>{{ $resource->sort_order }}</td>
                        <td>
                            <div class="d-flex flex-wrap gap-2">
                                <a href="{{ route('admin.professional-resources.edit', $resource) }}" class="btn btn-sm btn-primary">Rediger</a>
                                <a href="{{ route('admin.professional-resources.preview', $resource) }}" class="btn btn-sm btn-outline-secondary">Forhåndsvis</a>
                                @if ($resource->isPublished())
                                    <form method="POST" action="{{ route('admin.professional-resources.unpublish', $resource) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-warning">Avpubliser</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.professional-resources.publish', $resource) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-success">Publiser</button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('admin.professional-resources.destroy', $resource) }}" onsubmit="return confirm('Slette denne fagressursen? Dette kan ikke angres.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Slett</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">Det finnes ingen fagressurser ennå.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
