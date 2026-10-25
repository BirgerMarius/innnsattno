<!doctype html>
<html lang="no">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $dailyWord['title'] }}</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link href="{{ asset('css/custom/app.css') }}?v={{ filemtime(public_path('css/custom/app.css')) }}" rel="stylesheet">
</head>
<body class="daily-word-print-page">
    <div class="daily-word-print-actions no-print">
        <button type="button" onclick="window.print()">Skriv ut</button>
        <a href="{{ $printReturnUrl ?? route('tv') }}">Tilbake</a>
    </div>
    <main class="daily-word-print-sheet">
        <p class="daily-word-print-message">{{ $dailyWord['message'] }}</p>
        <footer>innsatt.no</footer>
    </main>
    @include('partials.print-redirect', ['fallbackUrl' => route('tv', [], false), 'autoPrint' => true])
</body>
</html>
