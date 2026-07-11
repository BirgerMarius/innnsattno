@extends('layouts.app') 
@section('title', 'Innsatt.no') 
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

<div class="row mb-3">

    <div class="col-md-6 mb-2">
    <div class="card text-center border-primary h-100">
            <div class="card-body py-2">
                <h5 class="mb-1">📅 Dato</h5>
                <h4 class="mb-0">
    {{ now()->locale('nb')->translatedFormat('j. F Y') }}
</h4>

<small class="text-muted">
    {{ ucfirst(now()->locale('nb')->translatedFormat('l')) }}
</small>
            </div>
        </div>
    </div>

    <div class="col-md-6 mb-2">
    <div class="card text-center border-success h-100">
            <div class="card-body py-2">
                <h5 class="mb-1">📆 Ukenummer</h5>
              <h1 class="mb-0 font-weight-bold text-success">
    UKE {{ $weekNumber }}
</h1>

<small class="text-muted d-block">
                    {{ $isEvenWeek ? 'Partallsuke' : 'Oddetallsuke' }}
                </small>
                </div>
        </div>
    </div>

</div>

<div class="alert alert-light text-center py-1 mb-3">

    <strong>
        🇳🇴 Neste flaggdag:
        {{ $formattedDate }} – {{ $nextFlagDayName }}
    </strong>

    <br>

    <small class="text-muted">
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

        <a href="/fotball" class="btn btn-warning btn-lg btn-block front-page-btn front-page-btn--shared" role="button">
            ⚽ Fotball-VM 2026
        </a>

        <a href="/tidsfordriv" class="btn btn-warning btn-lg btn-block front-page-btn front-page-btn--shared" role="button">
            🧩 Tidsfordriv – Sudoku
        </a>

        <a href="/ordjakt" class="btn btn-warning btn-lg btn-block front-page-btn front-page-btn--shared front-page-btn--wide" role="button">
            <i class="fas fa-puzzle-piece"></i> 🧩 Tidsfordriv – Ordjakt
        </a>

        <a href="{{ route('feedback.create') }}" class="btn btn-success btn-lg btn-block front-page-btn front-page-btn--wide front-page-btn--feedback" role="button">
            <span class="front-page-btn-title"><i class="far fa-comment-alt"></i> Har du en idé?</span>
            <small>Har du en idé eller har du oppdaget en feil?</small>
        </a>
    </div>
</div>

<p class="text-center text-muted mt-3">
    Husk å velge 2 sider per ark og skrive ut dobbeltsidig for å redusere papirforbruket.
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


<div class="text-center">
    <img src="https://www.yr.no/nb/innhold/1-2378693/meteogram.svg" width="100%">
</div>

<p class="text-center text-muted mt-4">
    Siden er sist oppdatert:
    {{ trim(shell_exec('git log -1 --format="%cd" --date=format:"%d.%m.%Y"')) }}
</p>

@include('partials.footer')


    </div>





@endsection
