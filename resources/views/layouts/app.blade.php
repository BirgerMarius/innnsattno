<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'INNSATT.NO')</title>

    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

    <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css"
    rel="stylesheet">

    <link rel="stylesheet"
          href="https://pro.fontawesome.com/releases/v5.13.0/css/all.css">

    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link href="{{ asset('css/custom/app.css') }}?v={{ filemtime(public_path('css/custom/app.css')) }}" rel="stylesheet">

@stack('styles')

@stack('head')

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
        })(window, document, "clarity", "script", "xf2dio3725");
    </script>

</head>
<body class="@yield('body-class')">

    @yield('content')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (() => {
            const isPrintPath = (path) => /(?:^|\/)(?:print|utskrift|fasit)$/.test(path);
            const returnTo = window.location.pathname + window.location.search + window.location.hash;

            document.querySelectorAll('a[href]').forEach((link) => {
                const url = new URL(link.href, window.location.href);
                if (url.origin === window.location.origin && isPrintPath(url.pathname)) {
                    url.searchParams.set('return_to', returnTo);
                    link.href = url.pathname + url.search + url.hash;
                }
            });

            document.querySelectorAll('form[action]').forEach((form) => {
                const url = new URL(form.action, window.location.href);
                if (url.origin !== window.location.origin || !isPrintPath(url.pathname)) return;
                const input = document.createElement('input');
                input.type = 'hidden'; input.name = 'return_to'; input.value = returnTo;
                form.appendChild(input);
            });
        })();
    </script>
   
    @stack('scripts')

</body>
</html>
