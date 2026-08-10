@extends('layouts.app')

@php($english = $settings['language'] === 'en')

@section('title', $quiz['title'].' | INNSATT.NO')

@section('content')
<div class="container page-container quiz-page">
    @include('partials.header')

    <main class="quiz-shell">
        <div class="quiz-result-header">
            <div>
                <p class="quiz-kicker">{{ $english ? 'Quiz preview' : 'Forhåndsvisning' }}</p>
                <h1>{{ $quiz['title'] }}</h1>
            </div>
            <div class="quiz-actions">
                <a href="{{ route('quiz.print') }}" class="btn btn-success">{{ $english ? 'Print quiz' : 'Skriv ut quiz' }}</a>
                <a href="{{ route('quiz.answer-key') }}" class="btn btn-outline-success">{{ $english ? 'Print answer key' : 'Skriv ut fasit' }}</a>
                <a href="{{ route('quiz.create') }}" class="btn btn-outline-secondary">{{ $english ? 'Create new quiz' : 'Lag ny quiz' }}</a>
            </div>
        </div>

        <dl class="quiz-meta">
            <div><dt>{{ $english ? 'Categories/topic' : 'Kategorier/tema' }}</dt><dd>{{ implode(', ', array_filter(array_merge($settings['category_labels'], [$settings['custom_topic']]))) }}</dd></div>
            <div><dt>{{ $english ? 'Difficulty' : 'Vanskelighetsgrad' }}</dt><dd>{{ $settings['difficulty_label'] }}</dd></div>
            <div><dt>{{ $english ? 'Questions' : 'Antall spørsmål' }}</dt><dd>{{ $settings['question_count'] }}</dd></div>
            <div><dt>{{ $english ? 'Language' : 'Språk' }}</dt><dd>{{ $settings['language_label'] }}</dd></div>
        </dl>

        <p class="quiz-source-note">{{ $english ? 'Questions are generated from open data sources, including Wikidata.' : 'Spørsmålene er generert fra åpne datakilder, blant annet Wikidata.' }}</p>

        <ol class="quiz-preview-list">
            @foreach($quiz['questions'] as $question)
                <li>
                    <strong>{{ $question['question'] }}</strong>
                    @if($settings['question_type'] === 'multiple_choice')
                        <ol type="A" class="quiz-options">
                            @foreach($question['options'] as $option)<li>{{ $option }}</li>@endforeach
                        </ol>
                    @endif
                </li>
            @endforeach
        </ol>
    </main>

    @include('partials.footer')
</div>
@endsection
