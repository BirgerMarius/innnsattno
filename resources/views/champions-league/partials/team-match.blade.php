@php($roundLabel = $match['roundNumber'] ? 'Runde '.$match['roundNumber'] : ($match['stageName'] ?: ($match['round'] ?: 'Ligafase')))
<article class="cl-team-match">
    <div class="cl-team-match-header">{{ $roundLabel }} · {{ $match['dateLabel'] }}@if($match['timeLabel']) kl. {{ $match['timeLabel'] }}@endif · {{ $match['isHome'] ? 'Hjemme' : 'Borte' }}</div>
    <div class="cl-team-match-row">
        @if($match['homeEmblemUrl'])<img src="{{ $match['homeEmblemUrl'] }}" alt="" onerror="this.remove()">@endif
        <span @if($match['homeTeamId'] === $teamId) class="font-weight-bold" @endif>{{ $match['homeTeam'] }}</span>
        <span>–</span>
        @if($match['awayEmblemUrl'])<img src="{{ $match['awayEmblemUrl'] }}" alt="" onerror="this.remove()">@endif
        <span @if($match['awayTeamId'] === $teamId) class="font-weight-bold" @endif>{{ $match['awayTeam'] }}</span>
        <span class="cl-team-score">@if($match['isFinished']){{ $match['homeScore'] ?? '–' }}–{{ $match['awayScore'] ?? '–' }}@else{{ $match['statusLabel'] }}@endif</span>
    </div>
</article>
