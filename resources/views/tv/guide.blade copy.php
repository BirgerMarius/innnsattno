<!DOCTYPE html>
<html lang="nb">

<head>

    <!-- Global site tag (gtag.js) - Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=UA-163151131-1"></script>
    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }
        gtag('js', new Date());

        gtag('config', 'UA-163151131-1');
    </script>



    <meta charset="UTF-8">
    <meta name="robots" content="noindex">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>TV-guide - RF</title>


    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css"
        integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.13.0/css/all.css"
        integrity="sha384-IIED/eyOkM6ihtOiQsX2zizxFBphgnv1zbe1bKA+njdFzkr6cDNy16jfIKWu4FNH" crossorigin="anonymous">

    

    <style>
        div#names,
        div#picker {
            display: none;
        }

        div#headerNames {
            width: 100%;
            margin: 60px auto;
            background-color: #fff;
            color: #000;
            font-family: Georgia, serif;
            font-size: 175px;
            text-align: center;
            cursor: pointer;
            padding: 10px 20px 20px 20px;
            overflow: hidden;
            border: 2px solid #222;
        }

        .button {
            width: 200px;
            margin: 10px;
            padding: 20px;
            color: rgb(0, 0, 0);
            font-family: Arial, sans-serif;
            font-size: 30px;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            display: inline-block;
            cursor: pointer;
            border: none;
        }
    </style>

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


<div style="border:1px dotted red">
    <h1>
Siden legges ned 7. januar 2025</h1>
<br />
<br />
<br />
</div>



        <h1><i class="fad fa-tv-retro"></i> TV-guide RF</h1>


        <br />

        <br /> <br />
        (Husk å velge 2 sider pr. ark, og printe dobbeltsidig så unngår man å bruke for mye papir)
        <br />
        <br />
        <a href="/print" class="btn btn-primary btn-lg btn-block" tabindex="1" role="button" aria-disabled="true"><i
                class="far fa-print"></i> TV-guide for i dag</a>
        <br />
        <br />

        {{-- <iframe src="https://www.tvkampen.com/widget/638bd1bc1f1c1?heading=Sport&border_color=blue&autoscroll=0" frameborder="0" style="width: 600px; height: 500px; border: none"></iframe> --}}

        <br />
        <br />



        <script>
            var confetti = {
                maxCount: 150,
                speed: 2,
                frameInterval: 15,
                alpha: 1,
                gradient: !1,
                start: null,
                stop: null,
                toggle: null,
                pause: null,
                resume: null,
                togglePause: null,
                remove: null,
                isPaused: null,
                isRunning: null
            };
            ! function() {
                confetti.start = s, confetti.stop = w, confetti.toggle = function() {
                    e ? w() : s()
                }, confetti.pause = u, confetti.resume = m, confetti.togglePause = function() {
                    i ? m() : u()
                }, confetti.isPaused = function() {
                    return i
                }, confetti.remove = function() {
                    stop(), i = !1, a = []
                }, confetti.isRunning = function() {
                    return e
                };
                var t = window.requestAnimationFrame || window.webkitRequestAnimationFrame || window.mozRequestAnimationFrame ||
                    window.oRequestAnimationFrame || window.msRequestAnimationFrame,
                    n = ["rgba(30,144,255,", "rgba(107,142,35,", "rgba(255,215,0,", "rgba(255,192,203,", "rgba(106,90,205,",
                        "rgba(173,216,230,", "rgba(238,130,238,", "rgba(152,251,152,", "rgba(70,130,180,", "rgba(244,164,96,",
                        "rgba(210,105,30,", "rgba(220,20,60,"
                    ],
                    e = !1,
                    i = !1,
                    o = Date.now(),
                    a = [],
                    r = 0,
                    l = null;

                function d(t, e, i) {
                    return t.color = n[Math.random() * n.length | 0] + (confetti.alpha + ")"), t.color2 = n[Math.random() * n
                            .length | 0] + (confetti.alpha + ")"), t.x = Math.random() * e, t.y = Math.random() * i - i, t
                        .diameter = 10 * Math.random() + 5, t.tilt = 10 * Math.random() - 10, t.tiltAngleIncrement = .07 * Math
                        .random() + .05, t.tiltAngle = Math.random() * Math.PI, t
                }

                function u() {
                    i = !0
                }

                function m() {
                    i = !1, c()
                }

                function c() {
                    if (!i)
                        if (0 === a.length) l.clearRect(0, 0, window.innerWidth, window.innerHeight), null;
                        else {
                            var n = Date.now(),
                                u = n - o;
                            (!t || u > confetti.frameInterval) && (l.clearRect(0, 0, window.innerWidth, window.innerHeight),
                                function() {
                                    var t, n = window.innerWidth,
                                        i = window.innerHeight;
                                    r += .01;
                                    for (var o = 0; o < a.length; o++) t = a[o], !e && t.y < -15 ? t.y = i + 100 : (t
                                        .tiltAngle += t.tiltAngleIncrement, t.x += Math.sin(r) - .5, t.y += .5 * (Math.cos(
                                            r) + t.diameter + confetti.speed), t.tilt = 15 * Math.sin(t.tiltAngle)), (t.x >
                                        n + 20 || t.x < -20 || t.y > i) && (e && a.length <= confetti.maxCount ? d(t, n,
                                        i) : (a.splice(o, 1), o--))
                                }(),
                                function(t) {
                                    for (var n, e, i, o, r = 0; r < a.length; r++) {
                                        if (n = a[r], t.beginPath(), t.lineWidth = n.diameter, i = n.x + n.tilt, e = i + n
                                            .diameter / 2, o = n.y + n.tilt + n.diameter / 2, confetti.gradient) {
                                            var l = t.createLinearGradient(e, n.y, i, o);
                                            l.addColorStop("0", n.color), l.addColorStop("1.0", n.color2), t.strokeStyle = l
                                        } else t.strokeStyle = n.color;
                                        t.moveTo(e, n.y), t.lineTo(i, o), t.stroke()
                                    }
                                }(l), o = n - u % confetti.frameInterval), requestAnimationFrame(c)
                        }
                }

                function s(t, n, o) {
                    var r = window.innerWidth,
                        u = window.innerHeight;
                    window.requestAnimationFrame = window.requestAnimationFrame || window.webkitRequestAnimationFrame || window
                        .mozRequestAnimationFrame || window.oRequestAnimationFrame || window.msRequestAnimationFrame ||
                        function(t) {
                            return window.setTimeout(t, confetti.frameInterval)
                        };
                    var m = document.getElementById("confetti-canvas");
                    null === m ? ((m = document.createElement("canvas")).setAttribute("id", "confetti-canvas"), m.setAttribute(
                            "style", "display:block;z-index:999999;pointer-events:none;position:fixed;top:0"), document.body
                        .prepend(m), m.width = r, m.height = u, window.addEventListener("resize", function() {
                            m.width = window.innerWidth, m.height = window.innerHeight
                        }, !0), l = m.getContext("2d")) : null === l && (l = m.getContext("2d"));
                    var s = confetti.maxCount;
                    if (n)
                        if (o)
                            if (n == o) s = a.length + o;
                            else {
                                if (n > o) {
                                    var f = n;
                                    n = o, o = f
                                }
                                s = a.length + (Math.random() * (o - n) + n | 0)
                            }
                    else s = a.length + n;
                    else o && (s = a.length + o);
                    for (; a.length < s;) a.push(d({}, r, u));
                    e = !0, i = !1, c(), t && window.setTimeout(w, t)
                }

                function w() {
                    e = !1
                }
            }();


            window.onload = function() {

                // Default variables
                let i = 0;
                let x = 0;
                // var namesDiv = document.getElementById("names");
                var namesListA = ["A-101", "A-102", "A-103", "A-104", "A-105", "A-106", "A-107", "A-108", "A-109", "A-110",
                    "A-111", "A-112", "A-113", "A-114", "A-115", "A-116", "A-201", "A-202", "A-203", "A-204", "A-205",
                    "A-206", "A-207", "A-301", "A-302", "A-303", "A-304", "A-305", "A-306", "A-307", "🃏"
                ];
                var namesListB = ["B-101", "B-102", "B-103", "B-104", "B-105", "B-106", "B-107", "B-201", "B-202", "B-203",
                    "B-204", "B-205", "B-206", "B-207", "B-208", "B-209", "B-210", "B-211", "B-212", "B-213", "B-214",
                    "B-301", "B-302", "B-303", "B-304", "B-305", "B-306", "B-307", "B-308", "B-309", "B-310", "B-311",
                    "B-312", "B-313", "B-314", "B-401", "B-402", "B-403", "B-404", "B-405", "B-406", "B-407", "B-408",
                    "B-409", "B-410", "B-411", "B-412", "B-413", "B-414", "🃏"
                ];
                var namesListC = ["C-101", "C-102", "C-103", "C-104", "C-105", "C-106", "C-107", "C-108", "C-109", "C-110",
                    "C-111", "C-112", "C-113", "C-114", "C-201", "C-202", "C-203", "C-204", "C-205", "C-206", "C-207",
                    "C-301", "C-302", "C-303", "C-304", "C-305", "C-306", "C-307", "C-308", "C-309", "C-310", "C-311",
                    "C-312", "C-313", "C-314", "C-401", "C-402", "C-403", "C-404", "C-405", "C-406", "C-407", "C-408",
                    "C-409", "C-410", "C-411", "C-412", "C-413", "C-414", "🃏"
                ];
                var namesListD = ["D-101", "D-102", "D-103", "D-104", "D-105", "D-106", "D-107", "D-108", "D-109", "D-110",
                    "D-111", "D-112", "D-113", "D-114", "D-201", "D-202", "D-203", "D-204", "D-205", "D-206", "D-207",
                    "D-208", "D-209", "D-210", "D-211", "D-212", "D-213", "D-214", "🃏"
                ];
                var textarea = document.querySelector('textarea#names');

                var pickerDiv = document.getElementById("picker");
                let intervalHandle = null;
                var headerOne = document.getElementById("headerNames");

                pickerDiv.style.display = "block";

                // Start the name shuffle on button click
                document.getElementById("startButtonA").addEventListener("click", function() {
                    confetti.remove();
                    clearInterval(intervalHandle);
                    shuffle(namesListA);


                    intervalHandle = setInterval(function() {
                        headerNames.textContent = namesListA[i++ % namesListA.length];
                    }, 100);

                    setTimeout(function() {
                        clearInterval(intervalHandle);
                        confetti.start();
                    }, 3000);

                });

                // Start the name shuffle on button click
                document.getElementById("startButtonB").addEventListener("click", function() {
                    confetti.remove();
                    clearInterval(intervalHandle);
                    shuffle(namesListB);


                    intervalHandle = setInterval(function() {
                        headerNames.textContent = namesListB[i++ % namesListB.length];
                    }, 100);

                    setTimeout(function() {
                        clearInterval(intervalHandle);
                        confetti.start();
                    }, 3000);

                });

                // Start the name shuffle on button click
                document.getElementById("startButtonC").addEventListener("click", function() {
                    confetti.remove();
                    clearInterval(intervalHandle);
                    shuffle(namesListC);


                    intervalHandle = setInterval(function() {
                        headerNames.textContent = namesListC[i++ % namesListC.length];
                    }, 100);

                    setTimeout(function() {
                        clearInterval(intervalHandle);
                        confetti.start();
                    }, 3000);

                });

                // Start the name shuffle on button click
                document.getElementById("startButtonD").addEventListener("click", function() {
                    confetti.remove();
                    clearInterval(intervalHandle);
                    shuffle(namesListD);


                    intervalHandle = setInterval(function() {
                        headerNames.textContent = namesListD[i++ % namesListD.length];
                    }, 100);

                    setTimeout(function() {
                        clearInterval(intervalHandle);
                        confetti.start();
                    }, 3000);

                });


                function shuffle(array) {

                    var currentIndex = array.length,
                        temporaryValue, randomIndex;

                    // While there remain elements to shuffle...
                    while (0 !== currentIndex) {

                        // Pick a remaining element...
                        randomIndex = Math.floor(Math.random() * currentIndex);
                        currentIndex -= 1;

                        // And swap it with the current element.
                        temporaryValue = array[currentIndex];
                        array[currentIndex] = array[randomIndex];
                        array[randomIndex] = temporaryValue;
                    }

                    return array;
                }

            }
        </script>




        <div id="picker">


            <center>
                <button class="button" id="startButtonA" style="background-color: rgb(255, 248, 35);">Avd. A</button>
                <button class="button" id="startButtonB" style="background-color: rgb(1, 202, 88);">Avd. B</button>
                <button class="button" id="startButtonC" style="background-color: rgb(0, 77, 220);">Avd. C</button>
                <button class="button" id="startButtonD" style="background-color: rgb(166, 0, 255);">Avd. D</button>
            </center>

            <div id="headerNames">?</div>

        </div>





      




        <br />
        <br />
        <br />
        <center>
            <img src="https://www.yr.no/nb/innhold/1-2378693/meteogram.svg" width="100%">
        </center>
        <br />
        <br />
        <br />



	

        



    </div>





</body>

</html>
