@extends('layouts.app')
@section('title', 'Mat og oppskrifter | INNSATT.NO')
@section('content')
<div class="container page-container recipe-page">@include('partials.header')
<main class="recipe-shell">
    <a class="recipe-back" href="{{ route('tv') }}">← Tilbake til forsiden</a>
    <header class="recipe-intro"><p class="recipe-kicker">Enkel bakst</p><h1>Mat og oppskrifter</h1><p>Her finner du enkle oppskrifter på brød, rundstykker og bakst. Oppskriftene kan leses på skjerm eller skrives ut.</p></header>
    <form method="get" action="{{ route('recipes.print-selected') }}" class="recipe-selection">
        <div class="recipe-selection-head"><p>Velg oppskrifter du vil skrive ut samlet.</p><button class="btn btn-primary" type="submit">Skriv ut valgte</button></div>
        @foreach($recipes->groupBy('category') as $category => $categoryRecipes)
            <section class="recipe-list-section" aria-labelledby="category-{{ \Illuminate\Support\Str::slug($category) }}">
                <h2 id="category-{{ \Illuminate\Support\Str::slug($category) }}">{{ $category }}</h2>
                <ul class="recipe-list">@foreach($categoryRecipes as $recipe)<li><div><a href="{{ route('recipes.show', $recipe['slug']) }}">{{ $recipe['title'] }}</a><span>{{ $recipe['total'] }} · {{ $recipe['yield'] }}</span></div><label><input type="checkbox" name="oppskrift[]" value="{{ $recipe['slug'] }}"> Velg til utskrift</label></li>@endforeach</ul>
            </section>
        @endforeach
    </form>
</main>@include('partials.footer')</div>
@endsection
