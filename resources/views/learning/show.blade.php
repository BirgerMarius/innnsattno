@extends('layouts.app')

@section('title', $sheetData['title'].' | Lær noe nytt')

@section('content')
<div class="container page-container learning-page">
    @include('partials.header')

    <main class="learning-shell learning-sheet-screen">
        <nav class="learning-back" aria-label="Brødsmulesti"><a href="{{ route('learning.index') }}">← Lær noe nytt</a><span>{{ $categoryData['name'] }}</span></nav>
        <div class="learning-screen-actions"><a class="btn btn-primary" href="{{ route('learning.print', [$category, $sheet]) }}">Skriv ut læringsarket</a></div>
        @include('learning.partials.sheet')
    </main>

    @include('partials.footer')
</div>
@endsection
