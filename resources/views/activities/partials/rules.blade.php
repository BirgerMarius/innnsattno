<article class="activity-rules">
    <header class="activity-rules-header"><p class="activity-kicker">{{ $activityData['kind'] }}</p><h1>{{ $activityData['title'] }}</h1><p>{{ $activityData['intro'] }}</p></header>
    <dl class="activity-facts"><div><dt>Antall deltakere</dt><dd>{{ $activityData['players'] }}</dd></div><div><dt>Du trenger</dt><dd>{{ $activityData['equipment'] }}</dd></div></dl>
    <section><h2>Sett opp spillet</h2><ol>@foreach($activityData['setup'] as $step)<li>{{ $step }}</li>@endforeach</ol></section>
    @if(($activityData['diagram'] ?? null) === 'backgammon')
        <figure class="activity-board" aria-labelledby="backgammon-caption">
            <div class="activity-board-title"><span>Mørk flytter ←</span><strong>BAR</strong><span>→ Lys flytter</span></div>
            <div class="activity-board-grid">
                @foreach([24, 23, 22, 21, 20, 19, 18, 17, 16, 15, 14, 13] as $point)<div class="activity-point"><b>{{ $point }}</b>@if(in_array($point, [24, 13]))<span class="activity-piece activity-piece--dark">{{ $point === 24 ? '2' : '5' }}</span>@elseif(in_array($point, [19, 17]))<span class="activity-piece activity-piece--light">{{ $point === 19 ? '5' : '3' }}</span>@endif</div>@endforeach
                @foreach([12, 11, 10, 9, 8, 7, 6, 5, 4, 3, 2, 1] as $point)<div class="activity-point"><b>{{ $point }}</b>@if(in_array($point, [8, 6]))<span class="activity-piece activity-piece--dark">{{ $point === 8 ? '3' : '5' }}</span>@elseif(in_array($point, [12, 1]))<span class="activity-piece activity-piece--light">{{ $point === 12 ? '5' : '2' }}</span>@endif</div>@endforeach
            </div>
            <figcaption id="backgammon-caption">Startoppsett for mørk. Lys setter brikkene speilvendt. Tallene viser punktene for mørk; lys teller motsatt vei.</figcaption>
        </figure>
    @endif
    <section><h2>Slik spiller dere</h2><ol>@foreach($activityData['turns'] as $step)<li>{{ $step }}</li>@endforeach</ol></section>
    @if(isset($activityData['special']))<section class="activity-special"><h2>Viktige regler i sjakk</h2><ul>@foreach($activityData['special'] as $rule)<li>{{ $rule }}</li>@endforeach</ul></section>@endif
    <section><h2>Slik avsluttes spillet</h2><p>{{ $activityData['ending'] }}</p></section>
    <aside class="activity-example"><h2>Eksempel</h2><p>{{ $activityData['example'] }}</p></aside>
    @if(isset($activityData['optional']))<p class="activity-optional"><strong>Valgfritt:</strong> {{ str_replace('Valgfritt: ', '', $activityData['optional']) }}</p>@endif
</article>
