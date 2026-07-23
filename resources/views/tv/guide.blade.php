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
</style>
@endpush

@section('content') 

<div class="container my-5">

@php
$flagDays = [
'01-01' => '1. nyttårsdag',
'21-01' => 'H.K.H. Prinsesse Ingrid Alexandra',
'06-02' => 'Samenes nasjonaldag',
'21-02' => 'H.M. Kong Harald V',
'01-05' => 'Arbeidernes dag',
'08-05' => 'Frigjørings- og veterandagen',
'17-05' => 'Grunnlovsdagen',
'07-06' => 'Unionsoppløsningen 1905',
'04-07' => 'H.M. Dronning Sonja',
'20-07' => 'H.K.H. Kronprins Haakon',
'29-07' => 'Olsokdagen',
'19-08' => 'H.K.H. Kronprinsesse Mette-Marit',
'25-12' => '1. juledag',
];

$easterSunday = \Carbon\Carbon::createFromTimestamp(
    easter_date(now()->year)
);

$holyThursday = $easterSunday->copy()->subDays(3);
$goodFriday = $easterSunday->copy()->subDays(2);
$easterMonday = $easterSunday->copy()->addDay();

$ascensionDay = $easterSunday->copy()->addDays(39);

$pentecostSunday = $easterSunday->copy()->addDays(49);
$pentecostMonday = $easterSunday->copy()->addDays(50);

$flagDays[$holyThursday->format('d-m')] = 'Skjærtorsdag';
$flagDays[$goodFriday->format('d-m')] = 'Langfredag';
$flagDays[$easterSunday->format('d-m')] = '1. påskedag';
$flagDays[$easterMonday->format('d-m')] = '2. påskedag';

$flagDays[$ascensionDay->format('d-m')] = 'Kristi himmelfartsdag';

$flagDays[$pentecostSunday->format('d-m')] = '1. pinsedag';
$flagDays[$pentecostMonday->format('d-m')] = '2. pinsedag';

uksort($flagDays, function ($a, $b) {
    [$dayA, $monthA] = explode('-', $a);
    [$dayB, $monthB] = explode('-', $b);

    return sprintf('%02d%02d', $monthA, $dayA)
         <=> sprintf('%02d%02d', $monthB, $dayB);
});

$today = now();

$nextFlagDay = null;
$nextFlagDayName = null;

$upcomingFlagDays = [];

foreach ($flagDays as $date => $name) {


[$day, $month] = explode('-', $date);

$flagDate = \Carbon\Carbon::create(now()->year, $month, $day);

if ($flagDate->isToday() || $flagDate->isFuture()) {

    $upcomingFlagDays[] =
        $flagDate->locale('nb')->translatedFormat('j. F')
        . ' - '
        . $name;

    if (!$nextFlagDay) {
        $nextFlagDay = $flagDate;
        $nextFlagDayName = $name;
    }
}


}

if (!$nextFlagDay) {
$firstDate = array_key_first($flagDays);
[$day, $month] = explode('-', $firstDate);


$nextFlagDay = \Carbon\Carbon::create(
    now()->year + 1,
    $month,
    $day
);

$nextFlagDayName = reset($flagDays);


}
$tooltipText = implode("\n", array_slice($upcomingFlagDays, 1, 10));
$formattedDate = $nextFlagDay
->locale('nb')
->translatedFormat('j. F');
@endphp

    @php
$todayText = now()->locale('nb')->translatedFormat('l j. F Y');
$weekNumber = now()->weekOfYear;
@endphp

@php
$isEvenWeek = $weekNumber % 2 === 0;
@endphp

<div class="alert alert-light text-center py-2 mb-3">
    <div class="front-page-date-row">
        <span class="front-page-date-item">
            <strong>📅 Dato:</strong>
            {{ ucfirst(now()->locale('nb')->translatedFormat('l')) }}
            {{ now()->locale('nb')->translatedFormat('j. F Y') }}
        </span>

        <span class="front-page-date-item">
            <strong>📆 Ukenummer:</strong>
            UKE {{ $weekNumber }}
            <span class="text-muted">({{ $isEvenWeek ? 'Partallsuke' : 'Oddetallsuke' }})</span>
        </span>

        <span class="front-page-date-item">
            <strong>🇳🇴 Neste flaggdag:</strong>
            {{ $formattedDate }} – {{ $nextFlagDayName }}
        </span>
    </div>

    <small class="text-muted d-block mt-1">
        Kommende flaggdager:
        {{ $tooltipText }}
    </small>
</div>

@include('partials.header')

<div class="front-page-actions">
    <div class="front-page-grid">
        <a href="/print" class="btn btn-primary btn-lg btn-block front-page-btn front-page-btn--ringerike" tabindex="1" role="button">
            <i class="far fa-print"></i> Skriv ut TV-guide for i dag - Ringerike fengsel
        </a>

        <a href="/print-ilseng" class="btn btn-danger btn-lg btn-block front-page-btn front-page-btn--ilseng" role="button">
            <i class="far fa-print"></i> Skriv ut TV-guide for i dag - Ilseng fengsel
        </a>

        <a href="/bonnetider" class="btn btn-primary btn-lg btn-block front-page-btn front-page-btn--ringerike" role="button">
            🕌 Bønnetider – Ringerike fengsel
        </a>

        <a href="/bonnetider-ilseng" class="btn btn-danger btn-lg btn-block front-page-btn front-page-btn--ilseng" role="button">
            🕌 Bønnetider – Ilseng fengsel
        </a>

        <a href="{{ route('weather.index') }}" class="btn btn-primary btn-lg btn-block front-page-btn front-page-btn--ringerike" role="button">
            🌦️ Værmelding – Tyristrand/Ringerike Fengsel
        </a>

        <a href="{{ route('visitation.index') }}" class="btn btn-primary btn-lg btn-block front-page-btn front-page-btn--ringerike" role="button">
            <svg class="front-page-wheel-icon" width="20" height="20" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                <circle cx="12" cy="13" r="8"></circle>
                <path d="M12 5v16M4 13h16M6.34 7.34l11.32 11.32M17.66 7.34 6.34 18.66M10 2h4l-2 3z"></path>
            </svg>
            Visitasjonsrullett – Ringerike fengsel
        </a>

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

        <a href="{{ route('feedback.create') }}" class="btn btn-success btn-lg btn-block front-page-btn front-page-btn--wide front-page-btn--feedback" role="button">
            <span class="front-page-btn-title"><i class="far fa-comment-alt"></i> Har du en idé?</span>
            <small>Har du en idé eller har du oppdaget en feil?</small>
        </a>

        <a href="{{ route('professional-resources.index') }}" class="btn btn-lg btn-block front-page-btn front-page-btn--professional front-page-btn--wide" role="button">
            <span class="front-page-btn-title"><i class="far fa-book-open"></i> Anbefalt fagstoff</span>
            <small>Utvalgte ressurser for ansatte og andre interesserte</small>
        </a>

        <a href="{{ route('news.index') }}" class="btn btn-lg btn-block front-page-btn front-page-btn--professional front-page-btn--wide" role="button">
            <span class="front-page-btn-title"><i class="far fa-newspaper"></i> Fagnyheter</span>
            <small>Nyheter fra kriminalomsorgen og beslektede fagområder</small>
        </a>
    </div>
</div>

<p class="text-center text-muted mt-3">
    Hver dag bidrar fengselsbetjenter til trygghet, håp og nye muligheter – med profesjonalitet, menneskelighet og mot gjør dere en uvurderlig forskjell for hele samfunnet.
</p>

<div class="front-page-secondary">
    <a href="https://www.kriminalomsorgen.no/ringerike-fengsel.5031519-237612.html"
       target="_blank"
       class="btn btn-primary btn-lg front-page-btn front-page-btn--ringerike">
        ℹ️ Ringerike fengsel
    </a>

    <a href="https://www.kriminalomsorgen.no/fengsel/innlandet-kriminalomsorgen-innlandet-avd-lavere-sikkerhet-ilseng"
       target="_blank"
       class="btn btn-danger btn-lg front-page-btn front-page-btn--ilseng">
        ℹ️ Ilseng fengsel
    </a>

    <a href="/oppdrag" class="btn btn-lg front-page-btn front-page-btn--task-wheel front-page-btn--wide">
        🎲 Spinn hjulet – Hvem får oppdraget?
    </a>
</div>

{{-- <iframe src="https://www.tvkampen.com/widget/638bd1bc1f1c1?heading=Sport&border_color=blue&autoscroll=0" frameborder="0" style="width: 600px; height: 500px; border: none"></iframe> --}}


<p class="text-center text-muted mt-4">
    Siden er sist oppdatert:
    {{ trim(shell_exec('git log -1 --format="%cd" --date=format:"%d.%m.%Y"')) }}
</p>

@include('partials.footer')


    </div>





@endsection
