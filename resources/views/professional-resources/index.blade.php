@extends('layouts.app')

@section('title', 'Anbefalt fagstoff')

@section('content')
<div class="container page-container professional-resources-page py-4">
    @include('partials.header')

    <main>
        <div class="professional-resources-intro mb-4">
            <h1 class="h2 mb-3">Anbefalt fagstoff</h1>
            <p>
                Her finner du et utvalg fagressurser som kan være nyttige for fengselsbetjenter, aspiranter
                og andre som arbeider i eller med kriminalomsorgen. Alle ressurser vurderes før de publiseres.
            </p>
            <p class="mb-0">
                Dette er ikke en offisiell side for kriminalomsorgen. Innholdet erstatter ikke lovverk,
                instrukser, opplæring eller lokale rutiner.
            </p>
        </div>

        @if (session('warning'))
            <div class="alert alert-warning" role="alert">{{ session('warning') }}</div>
        @endif

        @if ($hasPublishedResources)
            <form method="GET" action="{{ route('professional-resources.index') }}" class="professional-resource-filters mb-4">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="category" class="form-label">Kategori</label>
                        <select id="category" name="category" class="form-select">
                            <option value="">Alle kategorier</option>
                            @foreach ($categoryOptions as $categoryOption)
                                <option value="{{ $categoryOption->slug }}" @selected($selectedCategory === $categoryOption->slug)>{{ $categoryOption->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="media_type" class="form-label">Medietype</label>
                        <select id="media_type" name="media_type" class="form-select">
                            <option value="">Alle medietyper</option>
                            @foreach ($mediaTypes as $value => $mediaType)
                                <option value="{{ $value }}" @selected($selectedMediaType === $value)>{{ $mediaType['icon'] }} {{ $mediaType['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="tag" class="form-label">Emneord</label>
                        <select id="tag" name="tag" class="form-select">
                            <option value="">Alle emneord</option>
                            @foreach ($tagOptions as $tagOption)
                                <option value="{{ $tagOption->slug }}" @selected($selectedTag === $tagOption->slug)>{{ $tagOption->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary">Filtrer</button>
                        <a href="{{ route('professional-resources.index') }}" class="btn btn-outline-secondary">Nullstill filter</a>
                    </div>
                </div>
            </form>
        @endif

        @forelse ($categories as $category)
            <section class="professional-resource-category mb-5" aria-labelledby="category_{{ $category->id }}">
                <div class="mb-3">
                    <h2 id="category_{{ $category->id }}" class="h4 mb-2">{{ $category->name }}</h2>
                    @if ($category->description)
                        <p class="text-muted mb-0">{{ $category->description }}</p>
                    @endif
                </div>

                <div class="row g-3">
                    @foreach ($category->publishedResources as $resource)
                        <div class="col-md-6 col-xl-4">
                            <article class="card professional-resource-card h-100">
                                <div class="card-body d-flex flex-column">
                                    <div class="d-flex flex-wrap gap-2 mb-2">
                                        @if ($resource->is_featured)
                                            <span class="badge bg-success">Særlig anbefalt</span>
                                        @endif
                                        @if ($resource->mediaTypeDisplay())
                                            <span class="badge bg-light text-dark border">{{ $resource->mediaTypeDisplay() }}</span>
                                        @endif
                                    </div>

                                    <h3 class="h5">{{ $resource->title }}</h3>

                                    <p class="professional-resource-comment">{{ $resource->comment }}</p>

                                    <dl class="professional-resource-meta mb-4">
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
                                    </dl>

                                    @if ($resource->tags->isNotEmpty())
                                        <div class="professional-resource-tags mb-3" aria-label="Emneord">
                                            @foreach ($resource->tags as $tag)
                                                <span class="badge bg-light text-dark border">{{ $tag->name }}</span>
                                            @endforeach
                                        </div>
                                    @endif

                                    <a
                                        href="{{ $resource->url }}"
                                        class="btn btn-primary mt-auto professional-resource-link"
                                        target="_blank"
                                        rel="noopener noreferrer">
                                        Gå til ressurs
                                    </a>
                                </div>
                            </article>
                        </div>
                    @endforeach
                </div>
            </section>
        @empty
            @if ($hasPublishedResources)
                <div class="professional-resources-empty" role="status">
                    <h2 class="h4 mb-2">Ingen fagressurser passer filtrene</h2>
                    <p class="mb-3">Prøv en annen kategori, medietype eller et annet emneord.</p>
                    <a href="{{ route('professional-resources.index') }}" class="btn btn-outline-secondary">Vis alle</a>
                </div>
            @else
                <div class="professional-resources-empty" role="status">
                    <h2 class="h4 mb-2">Det publiseres fagressurser fortløpende</h2>
                    <p class="mb-0">
                        Siden er under oppbygging, og nye anbefalinger legges til etter hvert som de er vurdert og godkjent.
                    </p>
                </div>
            @endif
        @endforelse

        <p class="text-muted small mt-4 mb-0">
            Administrator? <a href="{{ route('admin.index') }}" class="text-muted">Administrasjon</a>
        </p>
    </main>

    @include('partials.footer')
</div>
@endsection
