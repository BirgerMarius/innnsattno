<html class="scroll-smooth">

<head>
    <title>Ringerike fengsel TV Guide</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <script src="https://cdn.tailwindcss.com"></script>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
    <style>
        body {
            /* height: {{ request()->query('px') ?? '38000' }}px; */
        }

        /* Hide scrollbar for Chrome, Safari and Opera */
        .example::-webkit-scrollbar {
            display: none;
        }

        /* Hide scrollbar for IE, Edge and Firefox */
        .example {
            -ms-overflow-style: none;
            /* IE and Edge */
            scrollbar-width: none;
            /* Firefox */
        }
    </style>





    <style>
        .pc {
            /* position: fixed; */
            /* top: 20%; */
            /* left: 30px; */
        }

        .bg {
            border-radius: 50%;
            width: 60px;
            height: 60px;
            background: #0f172a;
            margin-bottom: 10px;
        }

        .ol {
            border-radius: 50%;
            width: 44px;
            height: 44px;
            background: #0f172a;
            position: absolute;
            margin-top: 8px;
            margin-left: 8px;
        }
    </style>

    <script>
        // https://css-tricks.com/how-i-put-the-scroll-percentage-in-the-browser-title-bar/
        window.onscroll = () => {
            let scrollTop = window.scrollY;
            let docHeight = document.body.offsetHeight;
            let winHeight = window.innerHeight;
            let scrollPercent = scrollTop / (docHeight - winHeight);
            let scrollPercentRounded = Math.round(scrollPercent * 100);
            let degrees = scrollPercent * 360;
            document.querySelector(
                ".bg"
            ).style.background = `conic-gradient(#498 ${degrees}deg, #0f172a ${degrees}deg)`;
            document.querySelector(
                ".pb"
            );
        };
    </script>



    <script>
        function startTime() {
            const today = new Date();
            let h = today.getHours();
            let tt = ("0" + h).slice(-2);
            let m = today.getMinutes();
            let s = today.getSeconds();
            m = checkTime(m);
            s = checkTime(s);
            document.getElementById('txt').innerHTML = tt + ":" + m + ":" + s;
            setTimeout(startTime, 1000);
        }

        function checkTime(i) {
            if (i < 10) {
                i = "0" + i
            }; // add zero in front of numbers < 10
            return i;
        }
    </script>





</head>

<body class="px-36 text-gray-200 bg-slate-900 pt-96 text-{{ request()->query('tekst') ?? '7' }}xl example"
    onload="startTime()">








    <div class="fixed top-0 right-0 z-40 pt-4 pr-20">


        <div id="txt" class="inline-block font-bold "></div>



    </div>





    <div class="fixed bottom-0 right-0 z-50 p-10">

        <div class="pc">
            <div class="ol"></div>
            <div class="bg"></div>
        </div>

    </div>




    <div class="font-semibold text-slate-300 text-9xl mb-96">
        Ringerike fengsel<br />
        TV-GUIDE<br />
        <span class="text-slate-700 text-8xl">{{ \Carbon\Carbon::now()->locale('nb_NO')->dayName }}
            {{ \Carbon\Carbon::now()->locale('nb_NO')->format('d.m') }}</span>
    </div>








    @php
        $now = \Carbon\Carbon::now();
        $dst = $now->isDST();

        if ($dst == true) {
            $hours = 2;
        } else {
            $hours = 1;
        }
    @endphp



    @foreach ($channels as $channel)
        <span
            class="sticky top-0 block py-3 mb-16 font-bold border-b-8 border-red-800 bg-slate-900">{{ $channel['channel']['name'] }}</span>

        @foreach ($channel['listings'] as $listing)
            @if (\Carbon\Carbon::parse($listing['startsAt'])->addHours($hours)->format('Y-m-d H:i:s') < now())
            @else
                <div class="block">
                    <span
                        class="mr-6 font-bold text-slate-500">{{ \Carbon\Carbon::parse($listing['startsAt'])->addHours($hours)->format('H:i') }}</span>
                    <span class="">{{ $listing['title']['title'] }}</span>
                </div>
            @endif
        @endforeach

        <div class="mb-64"></div>
    @endforeach


    <span class="sticky top-0 block py-3 mb-16 font-bold border-b-8 border-red-800 bg-slate-900"> &nbsp;</span>


    <div class="h-screen"></div>
    <div class="h-screen"></div>
    <div class="h-screen"></div>













</body>


</html>
