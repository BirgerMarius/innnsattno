@php($tvBoxId = 'football-tv-'.$tvBoxContext)
<section class="football-tv-box {{ $tvBoxClass ?? '' }}" aria-labelledby="{{ $tvBoxId }}">
    <h2 id="{{ $tvBoxId }}">{{ $tvBoxTeam ?? false ? 'Neste' : 'Direktesendt' }} {{ $tvMatches['competitionLabel'] }} på TV</h2>
    @if($tvBoxShowSelection ?? true)<p class="football-tv-box__selection">Kanalutvalg: Ringerike fengsel</p>@endif
    @if(count($tvMatches['matches']))
        <div class="football-tv-box__list">
            @foreach($tvMatches['matches'] as $match)
                <article class="football-tv-box__match">
                    <strong>@if(isset($match['homeTeam']))@if($match['homeEmblemUrl'])<img class="football-tv-box__emblem" src="{{ $match['homeEmblemUrl'] }}" alt="" onerror="this.remove()">@endif{{ $match['homeTeam'] }} – @if($match['awayEmblemUrl'])<img class="football-tv-box__emblem" src="{{ $match['awayEmblemUrl'] }}" alt="" onerror="this.remove()">@endif{{ $match['awayTeam'] }}@else{{ $match['name'] }}@endif</strong>
                    <span>{{ $match['startsAt']->locale('nb')->translatedFormat('D j. F') }} · Sendestart {{ $match['startsAt']->format('H.i') }} · {{ $match['channel'] }}</span>
                </article>
            @endforeach
        </div>
    @elseif($tvMatches['missingMatchNames'])
        <p class="football-tv-box__status">Direktesendt {{ $tvMatches['competitionLabel'] }} er oppført, men kampnavn mangler.</p>
    @elseif($tvMatches['hasSourceFailure'])
        <p class="football-tv-box__status">TV-opplysninger kunne ikke lastes akkurat nå.</p>
    @else
        <p class="football-tv-box__status">Ingen identifiserbar direktesendt {{ $tvMatches['competitionLabel'] }}-kamp er oppført de neste 7 dagene.</p>
    @endif
    @if($tvMatches['hasSourceFailure'] && (count($tvMatches['matches']) || $tvMatches['missingMatchNames']))
        <p class="football-tv-box__note">TV-oversikten kan være ufullstendig{{ $tvMatches['usingStaleData'] ? '; sist lagrede data vises der det finnes.' : '.' }}</p>
    @endif
</section>
