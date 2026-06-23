<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'Innsatt.no')</title>

    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

<link rel="stylesheet"
href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">

<link rel="stylesheet"
href="https://pro.fontawesome.com/releases/v5.13.0/css/all.css">

<link href="{{ asset('css/app.css') }}" rel="stylesheet">

    @yield('head')

    <!-- Microsoft Clarity -->
    <script type="text/javascript">
        (function(c,l,a,r,i,t,y){
            c[a]=c[a]||function(){
                (c[a].q=c[a].q||[]).push(arguments)
            };
            t=l.createElement(r);
            t.async=1;
            t.src="https://www.clarity.ms/tag/"+i;
            y=l.getElementsByTagName(r)[0];
            y.parentNode.insertBefore(t,y);
        })(window, document, "clarity", "script", "DIN_CLARITY_ID");
    </script>
</head>
<body>

    @yield('content')

</body>
</html>