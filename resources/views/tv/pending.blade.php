@extends('layouts.app')
@section('title', 'TV-guide – '.$prison['name'])
@section('content')
<main class="container my-5"><div class="alert alert-info"><h1>TV-guiden er under klargjøring</h1><p>Kanallisten for {{ $prison['name'] }} er ikke mottatt ennå. Ingen andre fengslers kanalutvalg brukes.</p><p><a href="/{{ $prison['slug'] }}">Tilbake til startsiden</a></p></div></main>
@endsection
