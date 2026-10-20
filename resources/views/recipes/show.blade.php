@extends('layouts.app')
@section('title', $recipe['title'].' | Mat og oppskrifter')
@section('content')
<div class="container page-container recipe-page">@include('partials.header')<main class="recipe-shell recipe-show"><nav class="recipe-back" aria-label="Brødsmulesti"><a href="{{ route('recipes.index') }}">← Mat og oppskrifter</a></nav><div class="recipe-screen-actions"><a class="btn btn-primary" href="{{ route('recipes.print', $recipe['slug']) }}">Skriv ut oppskriften</a></div>@include('recipes.partials.recipe')</main>@include('partials.footer')</div>
@endsection
