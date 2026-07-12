<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Premier League API Test</title>
</head>
<body>
    <h1>Premier League API Test</h1>

    <p>Season: {{ $season }}</p>

    <ul>
        <li>{{ $reachable ? '✓' : '✗' }} API reachable</li>
        <li>{{ $competitionCount > 0 ? '✓' : '✗' }} Competition loaded ({{ $competitionCount }})</li>
        <li>{{ $teamCount > 0 ? '✓' : '✗' }} Teams loaded ({{ $teamCount }})</li>
        <li>{{ $standingCount > 0 ? '✓' : '✗' }} Standings loaded ({{ $standingCount }})</li>
        <li>Fixtures loaded ({{ $fixtureCount }})</li>
        <li>Results loaded ({{ $resultCount }})</li>
    </ul>

    @if ($error)
        <p>{{ $error }}</p>
    @endif
</body>
</html>
