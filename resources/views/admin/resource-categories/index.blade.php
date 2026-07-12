@extends('layouts.app')

@section('title', 'Kategorier - Anbefalt fagstoff')

@section('content')
<div class="container page-container py-4">
    @include('admin.professional-resources.partials.nav')

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Kategorier</h1>
            <p class="text-muted mb-0">Styr rekkefølge og synlighet for kategorier.</p>
        </div>
        <a href="{{ route('admin.resource-categories.create') }}" class="btn btn-success">Ny kategori</a>
    </div>

    @include('admin.professional-resources.partials.messages')

    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <caption class="visually-hidden">Kategorier</caption>
            <thead>
                <tr>
                    <th scope="col">Navn</th>
                    <th scope="col">Status</th>
                    <th scope="col">Sortering</th>
                    <th scope="col">Ressurser</th>
                    <th scope="col">Handlinger</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($categories as $category)
                    <tr>
                        <td>
                            <strong>{{ $category->name }}</strong>
                            @if ($category->description)
                                <div class="text-muted small">{{ $category->description }}</div>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $category->is_active ? 'bg-success' : 'bg-secondary' }}">
                                {{ $category->is_active ? 'Aktiv' : 'Inaktiv' }}
                            </span>
                        </td>
                        <td>{{ $category->sort_order }}</td>
                        <td>{{ $category->professional_resources_count }}</td>
                        <td>
                            <div class="d-flex flex-wrap gap-2">
                                <a href="{{ route('admin.resource-categories.edit', $category) }}" class="btn btn-sm btn-primary">Rediger</a>
                                <form method="POST" action="{{ route('admin.resource-categories.destroy', $category) }}" onsubmit="return confirm('Slette denne kategorien? Dette er bare mulig når kategorien er tom.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" @disabled($category->professional_resources_count > 0)>Slett</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">Det finnes ingen kategorier ennå.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
