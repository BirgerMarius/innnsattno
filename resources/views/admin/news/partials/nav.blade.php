@include('admin.partials.nav')

<div class="d-flex flex-wrap gap-2 mb-4" aria-label="Nyhetsmeny">
    <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.news.index') }}">Artikler</a>
    <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.news-sources.index') }}">Kilder</a>
    <a class="btn btn-sm btn-outline-secondary" href="{{ route('news.index') }}" target="_blank" rel="noopener noreferrer">Offentlig side</a>
</div>
