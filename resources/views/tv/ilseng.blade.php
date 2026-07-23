<!DOCTYPE html>
<html lang="nb">

<head>

  
    <meta charset="UTF-8">
    <meta name="robots" content="noindex">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>TV-guide - Ilseng Fengsel</title>


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


   <img src="/img/tv-header.png"
    class="img-fluid mb-4 rounded shadow"
     alt="TV-guide Ringerike Fengsel">


<a href="/print" class="btn btn-success btn-lg btn-block" tabindex="1" role="button">
    <i class="far fa-print"></i> Skriv ut TV-guide for i dag
</a>

<p class="text-center text-muted mt-3">
    Hver dag bidrar fengselsbetjenter til trygghet, håp og nye muligheter – med profesjonalitet, menneskelighet og mot gjør dere en uvurderlig forskjell for hele samfunnet.
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
