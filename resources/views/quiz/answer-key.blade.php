<!doctype html>
<html lang="{{ $settings['language'] === 'en' ? 'en' : 'nb' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $settings['language'] === 'en' ? 'Answer key' : 'Fasit' }} – {{ $quiz['title'] }}</title>
    <style>
        @page { size: A4 portrait; margin: 14mm; }
        * { box-sizing: border-box; }
        body { color: #111; font-family: Arial, sans-serif; font-size: 10pt; line-height: 1.3; margin: 0 auto; max-width: 182mm; }
        header { border-bottom: 2px solid #111; margin-bottom: 5mm; padding-bottom: 3mm; }
        h1 { font-size: 18pt; margin: 0; }
        h2 { font-size: 12pt; font-weight: normal; margin: 1mm 0 0; }
        ol { margin: 0; padding-left: 7mm; }
        li { break-inside: avoid; margin-bottom: 2.5mm; padding-left: 2mm; }
        .explanation { color: #444; display: block; font-size: 9pt; margin-top: .5mm; }
        .warning { color: #7a5200; display: block; font-size: 8.5pt; margin-top: .5mm; }
        .actions { margin: 10px auto; max-width: 182mm; }
        button, a { border-radius: 4px; display: inline-block; font: bold 14px Arial; margin-right: 6px; padding: 8px 12px; text-decoration: none; }
        button { background: #176b36; border: 0; color: #fff; }
        a { border: 1px solid #666; color: #222; }
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body>
    @php($english = $settings['language'] === 'en')
    <div class="actions no-print"><button type="button" onclick="window.print()">{{ $english ? 'Print answer key' : 'Skriv ut fasit' }}</button><a href="{{ route('quiz.show') }}">{{ $english ? 'Back' : 'Tilbake' }}</a></div>
    <header><h1>{{ $english ? 'Answer key' : 'Fasit' }}</h1><h2>{{ $quiz['title'] }}</h2></header>
    <ol>
        @foreach($quiz['questions'] as $question)
            <li>
                <strong>@if($settings['question_type'] === 'multiple_choice'){{ $question['correct_option'] }} – @endif{{ $question['correct_answer'] }}</strong>
                @if($question['explanation'] !== '')<span class="explanation">{{ $question['explanation'] }}</span>@endif
                @if($question['uncertain'])<span class="warning">⚠ {{ $english ? 'The answer could not be verified with high confidence.' : 'Svaret kunne ikke verifiseres med høy sikkerhet.' }}</span>@endif
            </li>
        @endforeach
    </ol>
</body>
</html>
