<!DOCTYPE html>
<html lang="nb">

<head>

  
    <meta charset="UTF-8">
    <meta name="robots" content="noindex">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>TV-guide - Ringerike Fengsel</title>


    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css"
        integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.13.0/css/all.css"
        integrity="sha384-IIED/eyOkM6ihtOiQsX2zizxFBphgnv1zbe1bKA+njdFzkr6cDNy16jfIKWu4FNH" crossorigin="anonymous">


<link href="{{ asset('css/app.css') }}" rel="stylesheet">

    <script type="text/javascript">
        (function(c, l, a, r, i, t, y) {
            c[a] = c[a] || function() {
                (c[a].q = c[a].q || []).push(arguments)
            };
            t = l.createElement(r);
            t.async = 1;
            t.src = "https://www.clarity.ms/tag/" + i;
            y = l.getElementsByTagName(r)[0];
            y.parentNode.insertBefore(t, y);
        })(window, document, "clarity", "script", "ekizv6kzel");
    </script>

</head>

<body>


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

$today = now();

$nextFlagDay = null;
$nextFlagDayName = null;

foreach ($flagDays as $date => $name) {


[$day, $month] = explode('-', $date);

$flagDate = \Carbon\Carbon::create(now()->year, $month, $day);

if ($flagDate->isToday() || $flagDate->isFuture()) {
    $nextFlagDay = $flagDate;
    $nextFlagDayName = $name;
    break;
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

$formattedDate = $nextFlagDay
->locale('nb')
->translatedFormat('j. F');
@endphp

    @php
$todayText = now()->locale('nb')->translatedFormat('l j. F Y');
$weekNumber = now()->weekOfYear;
@endphp

<div class="alert alert-secondary text-center py-2 mb-4">
    <strong>📅 {{ ucfirst($todayText) }}</strong> • Uke {{ $weekNumber }}
    <br>
    🇳🇴 Neste flaggdag: {{ $formattedDate }} – {{ $nextFlagDayName }}
</div>

   <img src="/img/tv-header.png"
    class="img-fluid mb-4 rounded shadow"
     alt="TV-guide Ringerike Fengsel">


<a href="/print" class="btn btn-success btn-lg btn-block" tabindex="1" role="button">
    <i class="far fa-print"></i> Skriv ut TV-guide for i dag - Ringerike fengsel
</a>

<a href="/ilseng" class="btn btn-primary btn-lg btn-block mt-2" role="button">
    <i class="far fa-print"></i> Skriv ut TV-guide for i dag - Ilseng fengsel - Kriminalomsorgen Innlandet 
</a>

<a href="/bonnetider" class="btn btn-info btn-lg btn-block mt-2" role="button">
    🕌 Bønnetider – Ringerike fengsel
</a>

<a href="/bonnetider-maned" class="btn btn-secondary btn-lg btn-block mt-2" role="button">
    🕌 Bønnetider – månedstabell
</a>

<p class="text-center text-muted mt-3">
    Husk å velge 2 sider per ark og skrive ut dobbeltsidig for å redusere papirforbruket.
</p>

<div class="mt-4 text-center">
    <a href="https://wheelofnames.com/" target="_blank" class="btn btn-outline-primary">
        🎲 Spin the wheel
    </a>

    <a href="https://www.kriminalomsorgen.no/ringerike-fengsel.5031519-237612.html"
       target="_blank"
       class="btn btn-outline-secondary">
        ℹ️ Ringerike fengsel
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

<div class="bg-dark text-white text-center p-3 mt-4 rounded">
        <h5>INNSATT.NO</h5>

    <p class="mb-2">
        TV-guide for Ringerike fengsel administreres av fengselsbetjent Birger Marius Kristiansen.
    </p>

    <p class="mb-0">
        Kontakt:
        <a href="mailto:innsatt@innsatt.no" class="text-light">
            innsatt@innsatt.no
        </a>
    </p>
</div>


    </div>





</body>

</html>
