<!doctype html>
<html lang="no">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $sheetData['title'] }}</title>
    <link href="{{ asset('css/custom/app.css') }}?v={{ filemtime(public_path('css/custom/app.css')) }}" rel="stylesheet">
</head>
<body class="learning-print-page">
    @php($returnUrl = route('learning.show', [$category, $sheet], false))
    <div class="learning-print-actions no-print"><button type="button" onclick="window.print()">Skriv ut</button><a href="{{ route('learning.show', [$category, $sheet]) }}">Tilbake</a></div>
    @include('learning.partials.sheet')
    @include('partials.print-redirect', ['fallbackUrl' => $returnUrl, 'autoPrint' => true])
</body>
</html>
