

@php
    $now = \Carbon\Carbon::now();
    $dst = $now->isDST();

    if ($dst == true){
        $hours = 2;
    } else {
        $hours = 1;
    }
@endphp




<h2 style="text-align:center; margin-bottom:10px;">
    TV-guide Ilseng fengsel
</h2>

<p style="text-align:center; margin-top:0; margin-bottom:20px;">
    {{ \Carbon\Carbon::now()->locale('nb_NO')->dayName }}
    {{ \Carbon\Carbon::now()->locale('nb_NO')->format('d.m.Y') }}
</p>
    
    
<br />





<div style="column-count:4; column-gap:0.6em; font-size:11pt; line-height:1.2;">



    

    @foreach ($channels as $channel)
        <div>
            <div style="margin-top:8px;">
    <strong>{{ $channel['channel']['name'] }}</strong>
</div>

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

<script>
    window.addEventListener('load', function () {
        window.print();
    });
</script>
