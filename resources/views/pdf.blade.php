

@php
    $now = \Carbon\Carbon::now();
    $dst = $now->isDST();

    if ($dst == true){
        $hours = 2;
    } else {
        $hours = 1;
    }
@endphp




<h2>Ringerike fengsel TV-guide {{ \Carbon\Carbon::now()->locale('nb_NO')->dayName }}
    {{ \Carbon\Carbon::now()->locale('nb_NO')->format('d.m') }}</h2>
    
    
<br />





<div style="column-count:4; column-gap: 0.5em; margin-left:5pt; font-size:11pt;">



    

    @foreach ($channels as $channel)
        <div>
            <strong>{{ $channel['channel']['name'] }}</strong><br />

            @foreach ($channel['listings'] as $listing)
                @if (\Carbon\Carbon::parse($listing['startsAt'])->addHours($hours)->format('Y-m-d H:i:s') < now())
                @else
                    {{ \Carbon\Carbon::parse($listing['startsAt'])->addHours($hours)->format('H:i') }}
                    {{ $listing['title']['title'] }}<br />
                @endif
            @endforeach
        </div>
        <br />
    @endforeach


</div>
