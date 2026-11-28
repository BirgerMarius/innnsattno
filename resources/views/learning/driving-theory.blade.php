@extends('layouts.app')

@section('title', 'Teoriprøve for klasse B | Lær noe nytt')

@section('content')
<div class="container page-container learning-page">
    @include('partials.header')

    <main class="learning-shell learning-sheet-screen">
        <nav class="learning-back" aria-label="Brødsmulesti">
            <a href="{{ route('learning.index') }}">← Lær noe nytt</a>
            <span>Hverdagsliv og samfunn</span>
        </nav>
        <header class="learning-intro">
            <h1>Teoriprøve for klasse B</h1>
            <p>Øv etter at du har lest «Veien til førerkortet». Velg ett svar per oppgave, og bruk fasiten til å se hva du bør lese mer om.</p>
            <p>Hos Statens vegvesen har du {{ $theory['facts']['minutes'] }} minutter på {{ $theory['facts']['questions'] }} oppgaver. Du trenger {{ $theory['facts']['pass'] }} riktige for å bestå. Oppgavene her er laget for øving.</p>
            <a class="btn btn-primary" href="{{ asset('pdf/teoriprovehefte-klasse-b.pdf') }}">Åpne utskriftsheftet (PDF)</a>
        </header>

        <div id="theory-questions">
            @php($questionNumber = 0)
            @foreach ($theory['sections'] as $section)
                <section class="mb-4" aria-labelledby="theory-section-{{ $loop->iteration }}">
                    <h2 class="h4" id="theory-section-{{ $loop->iteration }}">{{ $section['title'] }}</h2>
                    <ol start="{{ $questionNumber + 1 }}">
                        @foreach ($section['questions'] as $question)
                            @php($questionNumber++)
                            <li class="mb-3">
                                <fieldset data-answer="{{ $question['answer'] }}">
                                    <legend class="fs-6 fw-semibold mb-2">{{ $question['question'] }}</legend>
                                    @foreach ($question['options'] as $index => $option)
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="theory-{{ $questionNumber }}"
                                                id="theory-{{ $questionNumber }}-{{ $index }}" value="{{ $index }}">
                                            <label class="form-check-label" for="theory-{{ $questionNumber }}-{{ $index }}">
                                                {{ chr(65 + $index) }}. {{ $option }}
                                            </label>
                                        </div>
                                    @endforeach
                                </fieldset>
                            </li>
                        @endforeach
                    </ol>
                </section>
            @endforeach
        </div>

        <button class="btn btn-primary mb-2" type="button" id="theory-score-button" hidden>Sjekk svarene</button>
        <p id="theory-score" role="status"></p>
        <details class="mb-4" id="theory-answers">
            <summary>Vis fasit og forklaringer</summary>
            <ol class="mt-3">
                @foreach ($theory['sections'] as $section)
                    @foreach ($section['questions'] as $question)
                        <li class="mb-2">
                            <strong>{{ chr(65 + $question['answer']) }}. {{ $question['options'][$question['answer']] }}</strong>
                            <div>{{ $question['explanation'] }}</div>
                        </li>
                    @endforeach
                @endforeach
            </ol>
        </details>

        <details class="mb-4">
            <summary>Kilder og regelverk</summary>
            <p class="mt-2">Sjekket {{ $theory['checked'] }}.</p>
            <ul>
                @foreach ($theory['sources'] as [$label, $url])
                    <li><a href="{{ $url }}">{{ $label }}</a></li>
                @endforeach
            </ul>
        </details>
    </main>

    @include('partials.footer')
</div>

<script>
    (() => {
        const questions = document.querySelectorAll('#theory-questions fieldset[data-answer]');
        const button = document.getElementById('theory-score-button');
        const score = document.getElementById('theory-score');
        const answers = document.getElementById('theory-answers');

        button.hidden = false;
        button.addEventListener('click', () => {
            let correct = 0;
            let unanswered = 0;
            questions.forEach((question) => {
                const selected = question.querySelector('input:checked');
                if (!selected) {
                    unanswered++;
                } else if (selected.value === question.dataset.answer) {
                    correct++;
                }
            });
            answers.open = true;
            score.textContent = `${correct} av ${questions.length} riktige.`
                + (unanswered ? ` ${unanswered} ubesvart.` : '');
        });
    })();
</script>
@endsection
