@extends('layouts.app')

@section('title', 'Lær noe nytt | INNSATT.NO')

@section('content')
<div class="container page-container learning-page">
    @include('partials.header')

    <main class="learning-shell">
        <header class="learning-intro">
            <p class="learning-kicker">Prototype</p>
            <h1>Lær noe nytt</h1>
            <p>Her finner du korte læringsark om ting som kan være nyttige eller interessante å forstå. Hvert ark kan leses på skjerm eller skrives ut.</p>
        </header>

        <div class="learning-category-grid">
            @foreach($categories as $categorySlug => $category)
                <section class="learning-category" aria-labelledby="category-{{ $categorySlug }}">
                    <div class="learning-category-icon" aria-hidden="true">@include('learning.partials.icon', ['icon' => $category['icon']])</div>
                    <div>
                        <h2 id="category-{{ $categorySlug }}">{{ $category['name'] }}</h2>
                        <p>{{ $category['description'] }}</p>
                    </div>
                    <ul class="learning-sheet-list">
                        @foreach($category['sheets'] as $sheetSlug => $learningSheet)
                            <li><a href="{{ route('learning.show', [$categorySlug, $sheetSlug]) }}">{{ $learningSheet['title'] }} <span aria-hidden="true">→</span></a></li>
                        @endforeach
                    </ul>
                </section>
            @endforeach
        </div>
    </main>

    @include('partials.footer')
</div>
@endsection
