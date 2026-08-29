

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





<style>
    /* Keep programme text within its newspaper column on the standalone print view. */
    .ilseng-tv-print {
        column-count: 4;
        column-gap: 0.6em;
        font-size: 11pt;
        line-height: 1.2;
    }

    .ilseng-tv-print__channel {
        max-width: 100%;
        min-width: 0;
        overflow: hidden;
    }

    .ilseng-tv-print__channel-name {
        display: block;
        margin-top: 8px;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .ilseng-tv-print__listing {
        display: grid;
        grid-template-columns: 3.15em minmax(0, 1fr);
        column-gap: 0.2em;
        max-width: 100%;
        min-width: 0;
    }

    .ilseng-tv-print__time {
        white-space: nowrap;
    }

    .ilseng-tv-print__title {
        min-width: 0;
        overflow-wrap: anywhere;
        word-break: break-word;
    }
</style>

<div class="ilseng-tv-print">



    

    @foreach ($channels as $channel)
        <section class="ilseng-tv-print__channel">
            <strong class="ilseng-tv-print__channel-name">{{ $channel['channel']['name'] }}</strong>

            @foreach ($channel['listings'] as $listing)
                @if (\Carbon\Carbon::parse($listing['startsAt'])->addHours($hours)->format('Y-m-d H:i:s') < now())
                @else
                    <div class="ilseng-tv-print__listing">
                        <span class="ilseng-tv-print__time">{{ \Carbon\Carbon::parse($listing['startsAt'])->addHours($hours)->format('H:i') }}</span>
                        <span class="ilseng-tv-print__title">{{ $listing['title']['title'] }}</span>
                    </div>
                @endif
            @endforeach
        </section>
        <br />
    @endforeach


</div>

<script>
    let hasReturnedToTvGuide = false;

    window.addEventListener('afterprint', function () {
        if (hasReturnedToTvGuide) {
            return;
        }

        hasReturnedToTvGuide = true;
        window.location.replace(@json(route('tv', [], false)));
    });

    window.addEventListener('load', function () {
        window.print();
    }, { once: true });
</script>
