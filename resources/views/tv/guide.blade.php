@extends('layouts.app') 
@section('title', 'Innsatt.no') 
@section('body-class', !empty($theme) ? 'front-page-theme front-page-theme--'.$theme['id'] : '')

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
        padding: .2rem .45rem;
        border-radius: .3rem;
        white-space: normal;
        animation: front-page-flag-today-highlight 1.2s ease-in-out 3;
    }

    .front-page-flag-icon {
        width: 1.5em;
        height: auto;
        flex: 0 0 auto;
    }

    .front-page-mourning-flagging {
        width: 100%;
        box-sizing: border-box;
        margin: 0 0 1.25rem;
        padding: 1.35rem 1.5rem;
        color: #fff;
        text-align: left;
        background: #171717;
        border: 1px solid #3d3d3d;
        border-left: .4rem solid #a6a6a6;
        border-radius: .3rem;
        box-shadow: 0 .25rem .75rem rgba(0, 0, 0, .24);
        animation: front-page-mourning-attention 2s ease-in-out 4;
    }

    .front-page-mourning-heading {
        display: block;
        font-size: clamp(1.3rem, 3vw, 1.5rem);
        line-height: 1.25;
        letter-spacing: .01em;
    }

    .front-page-mourning-message {
        margin: .65rem 0 0;
        font-size: 1.05rem;
        line-height: 1.6;
    }

    .front-page-mourning-source {
        display: inline-block;
        margin-top: .8rem;
        color: #fff;
        font-size: .9rem;
        opacity: .82;
        text-underline-offset: .18em;
    }

    .front-page-mourning-source:hover,
    .front-page-mourning-source:focus-visible {
        color: #fff;
        opacity: 1;
    }

    @keyframes front-page-mourning-attention {
        50% {
            background-color: #252525;
            box-shadow: 0 .25rem 1.1rem rgba(0, 0, 0, .42);
        }
    }

    @keyframes front-page-flag-today-highlight {
        50% {
            background-color: rgba(0, 32, 91, .08);
            box-shadow: 0 0 .6rem rgba(186, 12, 47, .18);
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .front-page-flag-today,
        .front-page-mourning-flagging {
            animation: none;
        }
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
        .front-page-mourning-flagging {
            padding: 1.1rem;
        }

        .front-page-mourning-message {
            font-size: 1rem;
        }

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

@include('tv.partials.theme-art')

<div class="container my-5">

    @include('tv.partials.theme-preview')

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
    @if ($flagDayOverview['mourning_flagging'])
        @php
            $mourningFlagging = $flagDayOverview['mourning_flagging'];
        @endphp
        <section class="front-page-mourning-flagging" aria-label="{{ $mourningFlagging['title'] }}">
            <strong class="front-page-mourning-heading">{{ $mourningFlagging['title'] }}</strong>
            @if ($mourningFlagging['message'])
                <p class="front-page-mourning-message">{{ $mourningFlagging['message'] }}</p>
            @endif
            @if ($mourningFlagging['source_url'])
                <a class="front-page-mourning-source"
                   href="{{ $mourningFlagging['source_url'] }}"
                   target="_blank"
                   rel="noopener noreferrer">Offisiell informasjon: {{ $mourningFlagging['source_name'] }}</a>
            @endif
        </section>
    @endif

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
                <img class="front-page-flag-icon" src="{{ asset('img/norwegian-flag.svg') }}" alt="" aria-hidden="true">
                <strong>Det er flaggdag i dag:</strong>
                @if ($nextFlagDay['information_url'])
                    <a class="front-page-flag-link"
                       href="{{ $nextFlagDay['information_url'] }}"
                       target="_blank"
                       rel="noopener noreferrer">{{ $flagDayName($nextFlagDay) }}</a>
                @else
                    <span>{{ $flagDayName($nextFlagDay) }}</span>
                @endif
                <img class="front-page-flag-icon" src="{{ asset('img/norwegian-flag.svg') }}" alt="" aria-hidden="true">
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

        <a href="/champions-league" class="btn btn-warning btn-lg btn-block front-page-btn front-page-btn--shared" role="button">
            ⚽ Champions League 2026/27
        </a>

        <a href="/tidsfordriv" class="btn btn-warning btn-lg btn-block front-page-btn front-page-btn--shared" role="button">
            🧩 Tidsfordriv – Sudoku
        </a>

        <a href="/ordjakt" class="btn btn-warning btn-lg btn-block front-page-btn front-page-btn--shared" role="button">
            <i class="fas fa-puzzle-piece"></i> 🧩 Tidsfordriv – Ordjakt
        </a>

        <a href="{{ route('calendar.index') }}" class="btn btn-warning btn-lg btn-block front-page-btn front-page-btn--shared front-page-btn--wide" role="button">
            <i class="far fa-calendar-alt"></i> Månedskalender – For utskrift
        </a>

        <a href="{{ route('quiz.create') }}" class="btn btn-lg btn-block front-page-btn front-page-btn--test" role="button">
            ❓ Lag en quiz
            <span class="front-page-quiz-badges" aria-label="Ny testfunksjon"><span>NYHET</span><span>TEST</span></span>
        </a>

        <a href="{{ route('learning.index') }}" class="btn btn-lg btn-block front-page-btn front-page-btn--test" role="button">
            <i class="far fa-lightbulb" aria-hidden="true"></i> Lær noe nytt
            <span class="front-page-quiz-badges" aria-label="Ny testfunksjon"><span>NYHET</span><span>TEST</span></span>
        </a>
        </div>

        <a href="/oppdrag" class="btn btn-lg front-page-btn front-page-btn--task-wheel">
            <span>🎲</span>
            <span>Spinn hjulet</span>
            <small>Hvem får oppdraget?</small>
        </a>
    </section>

    @if (!empty($localNews))
        <section class="local-news-section" aria-labelledby="local-news-heading">
            <header class="local-news-section-header">
                <h2 id="local-news-heading">Lokale nyheter</h2>
                <p>Fra Ringerikes Blad</p>
            </header>
            @php
                $newsWithImages = array_filter($localNews, fn (array $article) => !empty($article['image_url']));
                $newsWithoutImages = array_filter($localNews, fn (array $article) => empty($article['image_url']));
            @endphp
            @if (!empty($newsWithImages))
                <div class="local-news-grid">
                    @foreach ($newsWithImages as $article)
                        <article class="local-news-card local-news-card--image">
                            <img class="local-news-card-image"
                                 src="{{ $article['image_url'] }}"
                                 alt=""
                                 loading="lazy"
                                 decoding="async">
                            <div class="local-news-card-content">
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
                                <h3>
                                    <a href="{{ $article['url'] }}"
                                       target="_blank"
                                       rel="noopener noreferrer">{{ $article['title'] }}</a>
                                </h3>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
            @if (!empty($newsWithoutImages))
                <div class="local-news-grid local-news-grid--text">
                    @foreach ($newsWithoutImages as $article)
                        <article class="local-news-card local-news-card--text">
                            <div class="local-news-card-content">
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
                                <h3>
                                    <a href="{{ $article['url'] }}"
                                       target="_blank"
                                       rel="noopener noreferrer">{{ $article['title'] }}</a>
                                </h3>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
            <a class="local-news-more"
               href="https://www.ringblad.no/ringerike-fengsel/"
               target="_blank"
               rel="noopener noreferrer">
                Se flere lokale nyheter hos Ringerikes Blad
            </a>
        </section>
    @endif

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
<div class="front-page-feedback">
    <a href="{{ route('feedback.create') }}" class="btn btn-lg front-page-btn front-page-btn--feedback" role="button">
        <span class="front-page-btn-title"><i class="far fa-comment-alt"></i> Har du en idé?</span>
        <small>Har du en idé eller har du oppdaget en feil?</small>
    </a>
</div>
</div>

{{-- <iframe src="https://www.tvkampen.com/widget/638bd1bc1f1c1?heading=Sport&border_color=blue&autoscroll=0" frameborder="0" style="width: 600px; height: 500px; border: none"></iframe> --}}


@if ($changeHistory['updated_at'])
    <p class="text-center text-muted mt-4">
        <a href="{{ route('change-history.index') }}" class="front-page-change-history-link text-muted">
            Siden er sist oppdatert: {{ $changeHistory['updated_at']->format('d.m.Y') }}
        </a>
    </p>
@endif

@include('partials.footer')

<p class="front-page-admin-link text-center small mb-0 mt-3">
    <a href="{{ route('admin.index') }}" class="text-muted">Administrasjon</a>
</p>


    </div>





@endsection
