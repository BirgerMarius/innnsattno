@extends('layouts.app')
@section('title','Nyheter')
@section('content')
@include('partials.header')
<main class="container page-container news-page">
 <div class="mb-4"><h1>Nyheter</h1><p class="lead">Kuraterte nyheter om kriminalomsorg, sikkerhet, arbeidsmiljø og rehabilitering.</p></div>
 <nav class="d-flex flex-wrap gap-2 mb-4" aria-label="Filtrer nyheter etter land">
  <a class="btn {{ !$filter?'btn-primary':'btn-outline-secondary' }}" href="{{ route('news.index') }}">Alle</a>
  @foreach($countries as $slug=>$country)<a class="btn {{ $filter===$slug?'btn-primary':'btn-outline-secondary' }}" href="{{ route('news.index',['land'=>$slug]) }}">{{ $country }}</a>@endforeach
 </nav>
 <div class="row g-4">
 @forelse($articles as $article)
  <article class="col-12 col-md-6"><div class="card h-100 news-card">
   @if($article->image_url)<img src="{{ $article->image_url }}" class="card-img-top news-card-image" alt="" loading="lazy" onerror="this.remove()">@endif
   <div class="card-body d-flex flex-column"><p class="small text-muted mb-2">{{ $article->source->name }} · {{ $article->source->country }} · {{ optional($article->published_at ?: $article->fetched_at ?: $article->created_at)->format('d.m.Y') }}</p>
    <h2 class="h4">{{ $article->displayTitle() }}</h2>@if($article->displayExcerpt())<p>{{ $article->displayExcerpt() }}</p>@endif
    <a class="btn btn-outline-primary mt-auto align-self-start" href="{{ $article->original_url }}" target="_blank" rel="noopener noreferrer">Les hos kilden</a>
   </div></div></article>
 @empty<div class="col-12"><div class="alert alert-info">Det er ingen publiserte nyheter i dette utvalget ennå.</div></div>@endforelse
 </div>
 <div class="mt-4">{{ $articles->links() }}</div>
</main>
<div class="container">@include('partials.footer')</div>
@endsection
