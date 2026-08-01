@extends('layouts.app') 
@section('title', 'Innsatt.no') 

@push('styles')
<style>
    .front-page-date-row {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem 1.5rem;
        align-items: center;
        justify-content: center;
    }

    .front-page-date-item {
        white-space: nowrap;
    }

    .front-page-flag-link {
        color: inherit;
        text-decoration: underline;
        text-decoration-color: transparent;
        text-underline-offset: .15em;
        transition: text-decoration-color .15s ease;
    }

    .front-page-flag-link:hover,
    .front-page-flag-link:focus-visible {
        color: inherit;
        text-decoration-color: currentColor;
    }

    .front-page-flag-link:focus-visible {
        outline: 2px solid currentColor;
        outline-offset: 2px;
        border-radius: 2px;
    }

    .front-page-flag-today {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .35rem;
        white-space: normal;
    }

    .front-page-upcoming-flag-days {
        display: inline-flex;
        flex-wrap: wrap;
        justify-content: center;
    }

    .front-page-upcoming-flag-day {
        white-space: nowrap;
    }

    .front-page-upcoming-flag-day-separator {
        margin: 0 .45em;
        white-space: nowrap;
    }

    @media (max-width: 575.98px) {
        .front-page-upcoming-flag-days {
            display: flex;
            flex-direction: column;
        }

        .front-page-upcoming-flag-day {
            white-space: normal;
        }

        .front-page-upcoming-flag-day-separator {
            display: none;
        }
    }
</style>
@endpush

@section('content')

<div class="container my-5">

    @php
$todayText = now()->locale('nb')->translatedFormat('l j. F Y');
$weekNumber = now()->weekOfYear;
$nextFlagDay = $flagDayOverview['next'];
$flagDayName = fn (array $flagDay) => $flagDay['name'].(isset($flagDay['age']) ? ' ('.$flagDay['age'].' år)' : '');
@endphp

@php
$isEvenWeek = $weekNumber % 2 === 0;
@endphp

<div class="alert alert-light text-center py-2 mb-3">
    <div class="front-page-date-row">
        <a class="front-page-date-item front-page-date-link" href="{{ route('today.show') }}">
            <strong>📅 Dato:</strong>
            {{ ucfirst(now()->locale('nb')->translatedFormat('l')) }}
            {{ now()->locale('nb')->translatedFormat('j. F Y') }}
            @if (!empty($todayNamedays))
                <span class="front-page-namedays">– {{ implode(', ', $todayNamedays) }}</span>
            @endif
        </a>

        <span class="front-page-date-item">
            <strong>📆 Ukenummer:</strong>
            UKE {{ $weekNumber }}
            <span class="text-muted">({{ $isEvenWeek ? 'Partallsuke' : 'Oddetallsuke' }})</span>
        </span>

        @if ($flagDayOverview['is_flag_day'])
            <span class="front-page-date-item front-page-flag-today">
                <span aria-hidden="true">🇳🇴</span>
                <strong>Det er flaggdag i dag:</strong>
                @if ($nextFlagDay['information_url'])
                    <a class="front-page-flag-link"
                       href="{{ $nextFlagDay['information_url'] }}"
                       target="_blank"
                       rel="noopener noreferrer">{{ $flagDayName($nextFlagDay) }}</a>
                @else
                    <span>{{ $flagDayName($nextFlagDay) }}</span>
                @endif
                <span aria-hidden="true">🇳🇴</span>
            </span>
        @else
            <span class="front-page-date-item">
                <strong><a class="front-page-flag-link"
                           href="{{ $flagDayOverview['official_overview_url'] }}"
                           target="_blank"
                           rel="noopener noreferrer">Neste flaggdag:</a></strong>
                {{ $nextFlagDay['date']->locale('nb')->translatedFormat('j. F') }} –
                @if ($nextFlagDay['information_url'])
                    <a class="front-page-flag-link"
                       href="{{ $nextFlagDay['information_url'] }}"
                       target="_blank"
                       rel="noopener noreferrer">{{ $flagDayName($nextFlagDay) }}</a>
                @else
                    <span>{{ $flagDayName($nextFlagDay) }}</span>
                @endif
            </span>
        @endif
    </div>

    <small class="text-muted d-block mt-1">
        Kommende flaggdager:
        <span class="front-page-upcoming-flag-days">
            @foreach ($flagDayOverview['upcoming'] as $upcomingFlagDay)
                @if (! $loop->first)<span class="front-page-upcoming-flag-day-separator" aria-hidden="true">|</span>@endif
                <span class="front-page-upcoming-flag-day">
                    <strong>{{ $upcomingFlagDay['date']->locale('nb')->translatedFormat('j. M. Y') }}</strong>:
                    @if ($upcomingFlagDay['information_url'])
                        <a class="front-page-flag-link"
                           href="{{ $upcomingFlagDay['information_url'] }}"
                           target="_blank"
                           rel="noopener noreferrer">{{ $flagDayName($upcomingFlagDay) }}</a>
                    @else
                        <span>{{ $flagDayName($upcomingFlagDay) }}</span>
                    @endif
                </span>
            @endforeach
        </span>
    </small>
</div>

@include('partials.header')

<div class="front-page-primary-area">
@if (!empty($localNews))
    <aside class="local-news-column" aria-labelledby="local-news-heading">
        <h2 id="local-news-heading">Lokale nyheter</h2>
        <p class="local-news-source">Fra Ringerikes Blad</p>
        <ol class="local-news-list">
            @foreach ($localNews as $article)
                <li class="local-news-item">
                    <a href="{{ $article['url'] }}"
                       target="_blank"
                       rel="noopener noreferrer">
                        {{ $article['title'] }}
                    </a>
                    @if (!empty($article['published_at']) || !empty($article['is_subscription']))
                        <div class="local-news-meta">
                            @if (!empty($article['published_at']))
                                <time>{{ $article['published_at'] }}</time>
                            @endif
                            @if (!empty($article['is_subscription']))
                                <span class="local-news-subscription">Abonnement</span>
                            @endif
                        </div>
                    @endif
                </li>
            @endforeach
        </ol>
        <a class="local-news-more"
           href="https://www.ringblad.no/ringerike-fengsel/"
           target="_blank"
           rel="noopener noreferrer">
            Se emnesiden hos Ringblad
        </a>
    </aside>
@endif

<div class="front-page-actions">
    <section class="prison-actions" aria-label="Tjenester for fengslene">
        <div class="prison-actions-column prison-actions-column--ringerike">
            <a href="/print" class="btn btn-primary btn-lg front-page-btn front-page-btn--ringerike" role="button">
                <i class="far fa-print" aria-hidden="true"></i> Skriv ut TV-guide – Ringerike fengsel
            </a>
            <a href="/bonnetider" class="btn btn-primary btn-lg front-page-btn front-page-btn--ringerike" role="button">
                🕌 Bønnetider – Ringerike fengsel
            </a>
            <a href="{{ route('weather.index') }}" class="btn btn-primary btn-lg front-page-btn front-page-btn--ringerike" role="button">
                🌦️ Værmelding – Tyristrand/Ringerike fengsel
            </a>
            <a href="{{ route('visitation.index') }}" class="btn btn-primary btn-lg front-page-btn front-page-btn--ringerike" role="button">
                <svg class="front-page-wheel-icon" width="20" height="20" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <circle cx="12" cy="13" r="8"></circle>
                    <path d="M12 5v16M4 13h16M6.34 7.34l11.32 11.32M17.66 7.34 6.34 18.66M10 2h4l-2 3z"></path>
                </svg>
                Visitasjonsrullett – Ringerike fengsel
            </a>
            <a href="https://www.kriminalomsorgen.no/ringerike-fengsel.5031519-237612.html"
               target="_blank" rel="noopener"
               class="btn btn-primary btn-lg front-page-btn front-page-btn--ringerike">
                ℹ️ Ringerike fengsel
            </a>
        </div>

        <div class="prison-actions-column prison-actions-column--ilseng">
            <a href="/print-ilseng" class="btn btn-danger btn-lg front-page-btn front-page-btn--ilseng" role="button">
                <i class="far fa-print" aria-hidden="true"></i> Skriv ut TV-guide – Ilseng fengsel
            </a>
            <a href="/bonnetider-ilseng" class="btn btn-danger btn-lg front-page-btn front-page-btn--ilseng" role="button">
                🕌 Bønnetider – Ilseng fengsel
            </a>
            <a href="{{ route('weather.ilseng') }}" class="btn btn-danger btn-lg front-page-btn front-page-btn--ilseng" role="button">
                🌦️ Værmelding – Ilseng fengsel
            </a>
            <span class="prison-actions-placeholder" aria-hidden="true"></span>
            <a href="https://www.kriminalomsorgen.no/fengsel/innlandet-kriminalomsorgen-innlandet-avd-lavere-sikkerhet-ilseng"
               target="_blank" rel="noopener"
               class="btn btn-danger btn-lg front-page-btn front-page-btn--ilseng">
                ℹ️ Ilseng fengsel
            </a>
        </div>
    </section>

    <section class="shared-actions" aria-label="Sport, tidsfordriv og utskrift">
        <div class="front-page-grid">
        <a href="/premier-league" class="btn btn-warning btn-lg btn-block front-page-btn front-page-btn--shared" role="button">
            ⚽ Premier League 2026/27
        </a>

        <a href="/eliteserien" class="btn btn-warning btn-lg btn-block front-page-btn front-page-btn--shared" role="button">
            ⚽ Eliteserien 2026
        </a>

        <a href="/tidsfordriv" class="btn btn-warning btn-lg btn-block front-page-btn front-page-btn--shared" role="button">
            🧩 Tidsfordriv – Sudoku
        </a>

        <a href="/ordjakt" class="btn btn-warning btn-lg btn-block front-page-btn front-page-btn--shared front-page-btn--wide" role="button">
            <i class="fas fa-puzzle-piece"></i> 🧩 Tidsfordriv – Ordjakt
        </a>

        <a href="{{ route('calendar.index') }}" class="btn btn-warning btn-lg btn-block front-page-btn front-page-btn--shared front-page-btn--wide" role="button">
            <i class="far fa-calendar-alt"></i> Månedskalender – For utskrift
        </a>
        </div>

        <a href="/oppdrag" class="btn btn-lg front-page-btn front-page-btn--task-wheel">
            <span>🎲</span>
            <span>Spinn hjulet</span>
            <small>Hvem får oppdraget?</small>
        </a>
    </section>

    <section class="front-page-grid front-page-content-actions" aria-label="Faglig innhold">
        <a href="{{ route('professional-resources.index') }}" class="btn btn-lg btn-block front-page-btn front-page-btn--professional front-page-btn--wide" role="button">
            <span class="front-page-btn-title"><i class="far fa-book-open"></i> Anbefalt fagstoff</span>
            <small>Utvalgte ressurser for ansatte og andre interesserte</small>
        </a>

        <a href="{{ route('news.index') }}" class="btn btn-lg btn-block front-page-btn front-page-btn--professional front-page-btn--wide" role="button">
            <span class="front-page-btn-title"><i class="far fa-newspaper"></i> Fagnyheter</span>
            <small>Nyheter fra kriminalomsorgen og beslektede fagområder</small>
        </a>
    </section>
</div>

<div class="front-page-feedback">
    <a href="{{ route('feedback.create') }}" class="btn btn-lg front-page-btn front-page-btn--feedback" role="button">
        <span class="front-page-btn-title"><i class="far fa-comment-alt"></i> Har du en idé?</span>
        <small>Har du en idé eller har du oppdaget en feil?</small>
    </a>
</div>
</div>

{{-- <iframe src="https://www.tvkampen.com/widget/638bd1bc1f1c1?heading=Sport&border_color=blue&autoscroll=0" frameborder="0" style="width: 600px; height: 500px; border: none"></iframe> --}}


<p class="text-center text-muted mt-4">
    Siden er sist oppdatert:
    {{ trim(shell_exec('git log -1 --format="%cd" --date=format:"%d.%m.%Y"')) }}
</p>

@include('partials.footer')


    </div>





@endsection
