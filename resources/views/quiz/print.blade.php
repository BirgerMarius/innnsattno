<!doctype html>
<html lang="{{ $settings['language'] === 'en' ? 'en' : 'nb' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $quiz['title'] }}</title>
    <style>
        @page { size: A4 portrait; margin: 14mm; }
        * { box-sizing: border-box; }
        body { color: #111; font-family: Arial, sans-serif; font-size: 11pt; line-height: 1.35; margin: 0 auto; max-width: 182mm; }
        header { border-bottom: 2px solid #111; margin-bottom: 7mm; padding-bottom: 4mm; }
        h1 { font-size: 20pt; margin: 0 0 2mm; }
        .meta { color: #444; font-size: 9pt; }
        .questions { margin: 0; padding-left: 7mm; }
        .question { break-inside: avoid; margin: 0 0 5mm; padding-left: 2mm; }
        .question-text { font-weight: bold; }
        .answer-line { border-bottom: 1px solid #888; height: 9mm; }
        .options { list-style: none; margin: 2mm 0 0; padding: 0; }
        .options li { margin: 1mm 0; }
        .option-letter { display: inline-block; font-weight: bold; width: 7mm; }
        .actions { margin: 10px auto; max-width: 182mm; }
        button, a { border-radius: 4px; display: inline-block; font: bold 14px Arial; margin-right: 6px; padding: 8px 12px; text-decoration: none; }
        button { background: #176b36; border: 0; color: #fff; }
        a { border: 1px solid #666; color: #222; }
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body>
    @php($english = $settings['language'] === 'en')
    <div class="actions no-print"><button type="button" onclick="window.print()">{{ $english ? 'Print quiz' : 'Skriv ut quiz' }}</button><a href="{{ route('quiz.show') }}">{{ $english ? 'Back' : 'Tilbake' }}</a></div>
    <header>
        <h1>{{ $quiz['title'] }}</h1>
        <div class="meta">{{ $settings['question_count'] }} {{ $english ? 'questions' : 'spørsmål' }} · {{ $settings['difficulty_label'] }}</div>
    </header>
    <ol class="questions">
        @foreach($quiz['questions'] as $question)
            <li class="question">
                <div class="question-text">{{ $question['question'] }}</div>
                @if($settings['question_type'] === 'multiple_choice')
                    <ul class="options">
                        @foreach($question['options'] as $index => $option)<li><span class="option-letter">{{ chr(65 + $index) }}.</span>{{ $option }}</li>@endforeach
                    </ul>
                @else
                    <div class="answer-line"></div>
                @endif
            </li>
        @endforeach
    </ol>
</body>
</html>
