@extends('layouts.app')

@section('title', 'Lag quiz | INNSATT.NO')

@section('content')
<div class="container page-container quiz-page">
    @include('partials.header')

    <main class="quiz-shell">
        <div class="quiz-intro">
            <p class="quiz-kicker">For utskrift</p>
            <h1>Lag en quiz</h1>
            <p>Velg innhold og vanskelighetsgrad. Quizen og en separat fasit genereres automatisk fra åpne datakilder.</p>
        </div>

        <aside class="quiz-test-notice" role="note">
            <strong>Quiz-generatoren er under testing.</strong>
            Vi setter stor pris på tilbakemeldinger om spørsmål, vanskelighetsgrad, feil eller forslag til forbedringer.
            Skriv gjerne til <a href="mailto:innsatt@innsatt.no">innsatt@innsatt.no</a> eller bruk siden for <a href="{{ route('feedback.create') }}">forslag og tilbakemeldinger</a>.
        </aside>

        @if($errors->has('generation'))
            <div class="alert alert-danger" role="alert">{{ $errors->first('generation') }}</div>
        @endif

        @if($errors->any() && !$errors->has('generation'))
            <div class="alert alert-danger" role="alert">
                <strong>Kontroller valgene:</strong>
                <ul class="mb-0 mt-2">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('quiz.store') }}" class="quiz-form">
            @csrf

            <div class="quiz-form-grid">
                <fieldset class="quiz-panel">
                    <legend>Antall spørsmål</legend>
                    <div class="quiz-choice-row">
                        @foreach($questionCounts as $count)
                            <label class="quiz-choice">
                                <input type="radio" name="question_count" value="{{ $count }}" @checked((int) old('question_count', 20) === $count)>
                                <span>{{ $count }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <fieldset class="quiz-panel">
                    <legend>Vanskelighetsgrad</legend>
                    <div class="quiz-choice-row">
                        @foreach($difficulties as $key => $label)
                            <label class="quiz-choice">
                                <input type="radio" name="difficulty" value="{{ $key }}" @checked(old('difficulty', 'medium') === $key)>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <fieldset class="quiz-panel">
                    <legend>Språk</legend>
                    <div class="quiz-choice-row">
                        @foreach($languages as $key => $label)
                            <label class="quiz-choice">
                                <input type="radio" name="language" value="{{ $key }}" @checked(old('language', 'no') === $key)>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <fieldset class="quiz-panel">
                    <legend>Spørsmålstype</legend>
                    <div class="quiz-choice-row">
                        @foreach($questionTypes as $key => $label)
                            <label class="quiz-choice">
                                <input type="radio" name="question_type" value="{{ $key }}" @checked(old('question_type', 'open') === $key)>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            </div>

            <fieldset class="quiz-panel quiz-categories">
                <legend>Kategorier</legend>
                <p class="text-muted">Velg én eller flere. «Blandet» henter fra flere tilgjengelige kategorier.</p>
                <div class="quiz-category-grid">
                    @foreach($categories as $key => $label)
                        @php($available = in_array($key, $availableCategories, true))
                        <label class="quiz-check {{ $available ? '' : 'is-disabled' }}">
                            <input type="checkbox" name="categories[]" value="{{ $key }}" @checked($available && in_array($key, old('categories', ['mixed']), true)) @disabled(!$available)>
                            <span>{{ $label }}@if(!$available)<small>Kommer senere</small>@endif</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <div class="quiz-panel">
                <label for="custom_topic" class="form-label fw-bold">Eget tema</label>
                <input id="custom_topic" type="text" class="form-control" placeholder="F.eks. Andre verdenskrig i Norge" disabled>
                <div class="form-text">Egendefinerte temaer krever AI-generering og blir tilgjengelig når finansiering for AI-bruk er på plass.</div>
            </div>

            <div class="quiz-submit-row">
                <button type="submit" class="btn btn-primary btn-lg">Generer quiz</button>
                <p>Det kan ta litt tid mens spørsmålene lages.</p>
            </div>
        </form>
    </main>

    @include('partials.footer')
</div>
@endsection
