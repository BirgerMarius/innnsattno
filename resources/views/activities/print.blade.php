<!doctype html>
<html lang="no"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ $activityData['title'] }}</title><link href="{{ asset('css/custom/app.css') }}?v={{ filemtime(public_path('css/custom/app.css')) }}" rel="stylesheet"></head>
<body class="activity-print-page">
    <div class="activity-print-actions no-print"><button type="button" onclick="window.print()">Skriv ut</button><a href="{{ route('activities.show', $activity) }}">Tilbake</a></div>
    @include('activities.partials.rules')
    @include('partials.print-redirect', ['fallbackUrl' => route('activities.show', $activity, false), 'autoPrint' => true])
</body></html>
