@include('partials.tv-print-head', ['title' => 'TV-guide – Ringerike fengsel | Innsatt.no'])

@php
    $now = \Carbon\Carbon::now('Europe/Oslo');
@endphp




<h2 style="text-align:center; margin-bottom:10px;">
    TV-guide Ringerike fengsel
</h2>

<p style="text-align:center; margin-top:0; margin-bottom:20px;">
    {{ $printDate->copy()->locale('nb_NO')->dayName }}
    {{ $printDate->format('d.m.Y') }}
</p>
@if (! $tvAvailable)
    <p role="status">TV-programmet er ikke tilgjengelig eller er ennå ikke publisert.</p>
@elseif ($tvLastUpdated)
    <p>Sist oppdatert: {{ $tvLastUpdated->format('d.m.Y H:i') }}</p>
@endif
    
    
<br />




<style>
    /* This print view has no shared stylesheet.  Keep every channel and programme
       inside its own column-width formatting context so text can never paint into
       the next newspaper column. */
    .ringerike-tv-print {
        column-count: 4;
        column-width: auto;
        column-fill: auto;
        column-gap: 0.6em;
        font-size: 11pt;
        line-height: 1.2;
    }

    .ringerike-tv-print__channel {
        box-sizing: border-box;
        display: block;
        width: 100%;
        max-width: 100%;
        min-width: 0;
        overflow: hidden;
    }

    .ringerike-tv-print__channel-name {
        display: block;
        max-width: 100%;
        width: 100%;
        break-after: avoid;
        margin: 0.45em 0 0.1em;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .ringerike-tv-print__listing {
        /* A block keeps the time and title together, while continuation lines can
           use the full column width. A two-track grid reserved the time gutter on
           every line and made otherwise ordinary titles needlessly tall. */
        display: block;
        box-sizing: border-box;
        break-inside: avoid;
        page-break-inside: avoid;
        max-width: 100%;
        min-width: 0;
        width: 100%;
        overflow: hidden;
    }

    .ringerike-tv-print__time {
        white-space: nowrap;
    }

    .ringerike-tv-print__title {
        overflow-wrap: anywhere;
        word-break: break-word;
    }
</style>

<div class="ringerike-tv-print">



    

    @foreach ($channels as $channel)
        <section class="ringerike-tv-print__channel">
            <strong class="ringerike-tv-print__channel-name">{{ $channel['channel']['name'] }}</strong>
            @php
                $listings = $channel['listings'] ?? [];
                $visibleListings = collect($listings)->filter(fn (array $listing) => $isSelectedDate
                    || \Carbon\Carbon::parse($listing['startsAt'])->setTimezone('Europe/Oslo')->greaterThanOrEqualTo($now));
            @endphp
            @if ($listings === [])
                <p>Programdata mangler.</p>
            @elseif ($visibleListings->isEmpty())
                <p>Alle dagens programmer er allerede sendt.</p>
            @else
                @foreach ($visibleListings as $listing)
                    @php($startsAt = \Carbon\Carbon::parse($listing['startsAt'])->setTimezone('Europe/Oslo'))
                    <div class="ringerike-tv-print__listing">
                        <span class="ringerike-tv-print__time">{{ $startsAt->format('H:i') }}</span>
                        <span class="ringerike-tv-print__title">{{ $listing['norwayMensMatchLabel'] ?? $listing['displayTitle'] ?? $listing['title']['title'] }}</span>
                    </div>
                @endforeach
            @endif
        </section>
    @endforeach


</div>

@include('partials.print-redirect', ['fallbackUrl' => route('tv', [], false), 'autoPrint' => true])
</body>
</html>
