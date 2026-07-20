@extends('layouts.app')
@section('title','Nyhetsadministrasjon')
@section('content')
<div class="container page-container py-4">@include('admin.news.partials.nav')
 <h1 class="h3">Nyheter</h1><p class="text-muted">Vurder og godkjenn innhentede artikler før offentlig publisering.</p>
 @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif @if(session('warning'))<div class="alert alert-warning">{{ session('warning') }}</div>@endif
 <div class="d-flex flex-wrap gap-2 mb-4"><a class="btn {{ !$activeStatus?'btn-primary':'btn-outline-secondary' }}" href="{{ route('admin.news.index') }}">Alle <span class="badge bg-light text-dark">{{ $allStatusCount }}</span></a>@foreach($statuses as $value=>$label)<a class="btn {{ $activeStatus===$value?'btn-primary':'btn-outline-secondary' }}" href="{{ route('admin.news.index',['status'=>$value]) }}">{{ $label }} <span class="badge bg-light text-dark">{{ $statusCounts[$value]??0 }}</span></a>@endforeach</div>
 @forelse($articles as $article)<article class="card mb-3"><div class="card-body"><div class="row g-3">
  @if($article->image_url)<div class="col-md-3"><img src="{{ $article->image_url }}" class="img-fluid rounded" alt="" onerror="this.parentElement.remove()"></div>@endif
  <div class="col"><p class="small text-muted">{{ $article->source->name }} · {{ $article->source->country }} | Publisert: {{ optional($article->published_at)->format('d.m.Y H:i')?:'Ukjent' }} | Hentet: {{ $article->fetched_at->format('d.m.Y H:i') }}</p><h2 class="h5">{{ $article->original_title }}</h2>@if($article->original_excerpt)<p>{{ $article->original_excerpt }}</p>@endif
   <p><span class="badge bg-secondary">{{ $statuses[$article->status] }}</span> <a href="{{ $article->original_url }}" target="_blank" rel="noopener noreferrer">Åpne originalartikkel</a></p>
   <form method="POST" action="{{ route('admin.news.update',$article) }}" class="mb-3">@csrf @method('PATCH')<label class="form-label">Visningstittel</label><input class="form-control mb-2" name="edited_title" value="{{ old('edited_title',$article->edited_title) }}" maxlength="500"><label class="form-label">Egen ingress</label><textarea class="form-control mb-2" name="edited_excerpt" maxlength="2000" rows="2">{{ old('edited_excerpt',$article->edited_excerpt) }}</textarea><button class="btn btn-sm btn-primary">Lagre tekst</button></form>
   <div class="d-flex flex-wrap gap-2">@foreach($statuses as $value=>$label)@if($article->status!==$value)<form method="POST" action="{{ route('admin.news.status',$article) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="{{ $value }}"><button class="btn btn-sm {{ $value==='published'?'btn-success':'btn-outline-secondary' }}">{{ $value==='pending'?'Flytt til nye':rtrim($label,'e') }}</button></form>@endif @endforeach</div>
  </div></div></div></article>@empty<div class="alert alert-info">Ingen artikler i dette utvalget.</div>@endforelse
 {{ $articles->links() }}
</div>@endsection
