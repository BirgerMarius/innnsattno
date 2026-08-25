@if (!empty($theme))
    <div class="front-page-theme-art front-page-theme-art--{{ $theme['id'] }}" aria-hidden="true">
        <svg viewBox="0 0 1600 1000" preserveAspectRatio="xMidYMid slice" focusable="false">
            @if ($theme['id'] === 'sensommer')
                <g class="theme-art-grass"><path d="M0 900c32-90 42-155 45-225M45 900c10-115 52-178 92-242M96 900c35-88 79-142 137-190M1450 1000c8-136 44-223 108-306M1510 1000c-4-120 29-205 78-279"/></g>
                <g class="theme-art-grain"><path d="M125 570c-3 85-3 164 0 240M125 575c-53 30-61 78-12 102M125 620c55 22 62 69 12 95M1500 540c-3 85-3 164 0 240M1500 545c-53 30-61 78-12 102M1500 590c55 22 62 69 12 95"/></g>
                <g class="theme-art-leaves"><path d="M240 185c52-36 84 17 45 60-42-2-61-25-45-60z"/><path d="M1325 255c50-35 82 16 45 59-41-2-61-24-45-59z"/></g>
            @elseif ($theme['id'] === 'tidlig-host')
                <g class="theme-art-early-branch"><path d="M0 230c110 15 165 85 290 155M0 640c95-25 165-4 280 80M1600 180c-100 30-140 100-250 170"/></g>
                <g class="theme-art-early-leaves"><path d="M90 258c55-38 88 14 49 60-43-2-63-25-49-60z"/><path d="M180 318c52-35 86 18 47 62-42-3-61-26-47-62z"/><path d="M260 380c52-36 86 17 47 62-42-3-61-26-47-62z"/><path d="M110 625c50-34 83 18 44 59-40-3-59-25-44-59z"/><path d="M1430 225c52-35 85 18 46 61-42-3-61-26-46-61z"/></g>
            @elseif ($theme['id'] === 'senhost')
                <g class="theme-art-bare-branch"><path d="M0 820c125-120 165-270 330-425M105 720l-22-155M198 605l105-95M1600 735c-120-120-170-260-335-390M1495 635l10-150M1390 510l-90-90"/></g>
                <g class="theme-art-late-leaves"><path d="M105 555c49-34 80 17 43 58-40-2-58-24-43-58z"/><path d="M285 485c48-32 78 18 41 56-39-3-56-24-41-56z"/><path d="M1450 470c48-32 78 18 41 56-39-3-56-24-41-56z"/></g>
            @elseif ($theme['id'] === 'forjul')
                <g class="theme-art-pine theme-art-pine--soft"><path d="M0 0l70 65-20-2 75 80-26-5 80 92-34-8 72 104-32-9 75 120H0z"/><path d="M1600 0l-70 65 20-2-75 80 26-5-80 92 34-8-72 104 32-9-75 120h210z"/></g>
                <path class="theme-art-wire" d="M0 120c230 65 390-40 650 10s520 65 950-15"/>
                <g class="theme-art-lights"><circle cx="180" cy="145" r="10"/><circle cx="405" cy="112" r="9"/><circle cx="660" cy="131" r="11"/><circle cx="930" cy="145" r="9"/><circle cx="1200" cy="130" r="11"/><circle cx="1450" cy="108" r="10"/></g>
            @elseif ($theme['id'] === 'nyttar')
                <g class="theme-art-fireworks"><path d="M170 270v-145M170 270l-92-110M170 270l95-110M170 270l-145-25M170 270l145-25M170 270l-60 118M170 270l60 118M1425 620v-155M1425 620l-105-112M1425 620l105-112M1425 620l-150-26M1425 620l150-26M1425 620l-72 120M1425 620l72 120"/></g>
                <g class="theme-art-confetti"><circle cx="320" cy="160" r="7"/><circle cx="120" cy="470" r="6"/><circle cx="1310" cy="280" r="7"/><circle cx="1510" cy="410" r="6"/><circle cx="1120" cy="770" r="7"/></g>
                <g class="theme-art-stars"><path d="M640 95l8 21 22 1-17 14 6 22-19-12-19 12 6-22-17-14 22-1z"/><path d="M1120 165l8 21 22 1-17 14 6 22-19-12-19 12 6-22-17-14 22-1z"/></g>
            @elseif ($theme['id'] === 'mork-vinter')
                <g class="theme-art-night-stars"><circle cx="100" cy="140" r="5"/><circle cx="235" cy="250" r="4"/><circle cx="145" cy="450" r="6"/><circle cx="1480" cy="130" r="5"/><circle cx="1350" cy="330" r="4"/><circle cx="1510" cy="560" r="6"/><circle cx="1290" cy="790" r="4"/></g>
                <g class="theme-art-snowflakes"><path d="M250 600v130M185 665h130M205 620l90 90M295 620l-90 90M1390 180v110M1335 235h110M1352 198l76 76M1428 198l-76 76"/></g>
                <g class="theme-art-frost"><path d="M0 820c95-55 150 35 235-20s155 25 255-45M1600 750c-95-55-150 35-235-20s-155 25-255-45"/></g>
            @elseif ($theme['id'] === 'tidlig-var')
                <g class="theme-art-thaw"><path d="M0 760c90-45 140 36 220-10s140 32 230-20M1600 710c-90-45-140 36-220-10s-140 32-230-20"/></g>
                <g class="theme-art-early-sprouts"><path d="M130 790c-2-95 15-158 52-213M182 635c-37-31-7-67 27-44-1 28-10 40-27 44zM1340 820c3-94 26-155 68-207M1400 675c-38-29-10-66 25-45 0 27-9 40-25 45z"/></g>
                <g class="theme-art-snowflakes"><path d="M180 275v70M145 310h70M155 285l50 50M205 285l-50 50M1460 380v70M1425 415h70M1435 390l50 50M1485 390l-50 50"/></g>
            @elseif ($theme['id'] === 'paske')
                <g class="theme-art-easter-branch"><path d="M0 700c115-85 175-210 315-300M1600 720c-110-85-165-205-300-300"/></g>
                <g class="theme-art-easter-leaves"><path d="M100 620c48-35 79 16 42 57-39-2-57-24-42-57z"/><path d="M185 530c48-35 79 16 42 57-39-2-57-24-42-57z"/><path d="M1490 640c-48-35-79 16-42 57 39-2 57-24 42-57z"/></g>
                <g class="theme-art-eggs"><ellipse cx="180" cy="350" rx="35" ry="47"/><ellipse cx="1420" cy="490" rx="35" ry="47"/><ellipse cx="128" cy="760" rx="26" ry="35"/><ellipse cx="255" cy="705" rx="29" ry="39"/><ellipse cx="1345" cy="650" rx="30" ry="41"/></g>
                <g class="theme-art-egg-pattern"><path d="M151 350h58M151 368h58M1392 490h57M1392 508h57M229 705h52M1319 650h52"/></g>
                <g class="theme-art-bunny"><ellipse cx="1450" cy="285" rx="19" ry="62" transform="rotate(-12 1450 285)"/><ellipse cx="1490" cy="285" rx="19" ry="62" transform="rotate(12 1490 285)"/><circle cx="1470" cy="365" r="48"/><ellipse cx="1438" cy="438" rx="55" ry="68"/><circle class="theme-art-bunny-eye" cx="1488" cy="356" r="5"/><path class="theme-art-bunny-nose" d="M1504 378l8 5-8 5z"/></g>
                <g class="theme-art-chick"><circle cx="280" cy="185" r="39"/><circle cx="280" cy="135" r="25"/><path d="M303 137l25 9-25 9z"/><circle class="theme-art-chick-eye" cx="290" cy="128" r="4"/></g>
            @elseif ($theme['id'] === '17-mai')
                <g class="theme-art-ribbons"><path d="M0 125c180 58 250-55 420 15s290 15 410-10M1600 170c-160 55-240-35-390 22s-275 8-380-22"/></g>
                <g class="theme-art-norwegian-flags">
                    <path class="theme-art-flagpole" d="M115 320v180M1480 440v180"/>
                    <rect class="theme-art-flag-red" x="115" y="330" width="120" height="78"/><rect class="theme-art-flag-white" x="145" y="330" width="20" height="78"/><rect class="theme-art-flag-white" x="115" y="359" width="120" height="20"/><rect class="theme-art-flag-blue" x="151" y="330" width="8" height="78"/><rect class="theme-art-flag-blue" x="115" y="365" width="120" height="8"/>
                    <rect class="theme-art-flag-red" x="1340" y="450" width="140" height="91"/><rect class="theme-art-flag-white" x="1375" y="450" width="23" height="91"/><rect class="theme-art-flag-white" x="1340" y="484" width="140" height="23"/><rect class="theme-art-flag-blue" x="1382" y="450" width="9" height="91"/><rect class="theme-art-flag-blue" x="1340" y="491" width="140" height="9"/>
                </g>
                <g class="theme-art-birch"><path d="M0 760c105-80 165-170 275-250M1600 780c-100-80-155-168-260-250"/><path d="M95 672c44-33 74 15 39 55-36-2-53-22-39-55zM170 595c44-33 74 15 39 55-36-2-53-22-39-55zM1500 690c-44-33-74 15-39 55 36-2 53-22 39-55z"/></g>
            @elseif ($theme['id'] === 'forsommer')
                <g class="theme-art-early-summer-clouds"><path d="M55 175c5-39 56-47 75-15 35-31 91-2 77 39H55zM1320 290c5-39 56-47 75-15 35-31 91-2 77 39h-152z"/></g>
                <g class="theme-art-forsommer-leaves"><path d="M100 600c56-42 95 14 55 66-43-1-65-25-55-66z"/><path d="M180 510c56-42 95 14 55 66-43-1-65-25-55-66z"/><path d="M1450 600c-56-42-95 14-55 66 43-1 65-25 55-66z"/></g>
                <g class="theme-art-flowers"><circle cx="260" cy="400" r="12"/><circle cx="236" cy="400" r="12"/><circle cx="248" cy="378" r="12"/><circle cx="248" cy="422" r="12"/><circle cx="1380" cy="720" r="12"/><circle cx="1356" cy="720" r="12"/><circle cx="1368" cy="698" r="12"/><circle cx="1368" cy="742" r="12"/></g>
            @elseif ($theme['id'] === 'midtsommer')
                <g class="theme-art-evening-sun"><circle cx="1380" cy="300" r="78"/><path d="M1270 360c80-38 160-38 250 0M0 810c145-65 265-65 410 0"/></g>
                <g class="theme-art-meadow"><path d="M0 1000c70-175 160-210 275-300M55 1000c35-120 110-190 200-245M1600 1000c-70-175-160-210-275-300M1545 1000c-35-120-110-190-200-245"/></g>
                <g class="theme-art-flowers"><circle cx="170" cy="670" r="15"/><circle cx="142" cy="670" r="15"/><circle cx="156" cy="644" r="15"/><circle cx="156" cy="696" r="15"/><circle cx="1450" cy="610" r="15"/><circle cx="1422" cy="610" r="15"/><circle cx="1436" cy="584" r="15"/><circle cx="1436" cy="636" r="15"/></g>
            @elseif ($theme['id'] === 'host')
                <g class="theme-art-branch"><path d="M0 155 C125 155 160 245 280 310 M0 620 C100 600 150 510 270 475"/><path d="M160 245 C145 185 115 150 70 120 M150 510 C205 540 245 575 290 640"/></g>
                <g class="theme-art-leaves"><path d="M48 145c55-38 87 12 51 57-42 1-63-20-51-57z"/><path d="M135 224c56-31 82 24 40 63-41-4-58-29-40-63z"/><path d="M214 302c50-35 83 15 48 59-43 1-64-21-48-59z"/><path d="M70 608c51-29 78 19 42 59-39-2-57-23-42-59z"/><path d="M162 510c52-35 85 19 45 64-43-3-61-27-45-64z"/><path d="M250 470c51-33 83 19 44 62-40-3-59-26-44-62z"/><path d="M1520 140c52-34 83 18 45 62-42-3-60-27-45-62z"/><path d="M1455 230c54-35 86 19 46 64-43-3-61-28-46-64z"/></g>
            @elseif ($theme['id'] === 'advent')
                <path class="theme-art-wire" d="M0 90 C250 155 460 42 720 100 S1220 170 1600 65"/>
                <g class="theme-art-lights"><circle cx="115" cy="112" r="12"/><circle cx="270" cy="126" r="9"/><circle cx="430" cy="97" r="13"/><circle cx="600" cy="85" r="9"/><circle cx="790" cy="113" r="12"/><circle cx="970" cy="132" r="9"/><circle cx="1160" cy="130" r="13"/><circle cx="1360" cy="95" r="10"/><circle cx="1510" cy="77" r="13"/></g>
                <g class="theme-art-stars"><path d="M105 305l8 21 22 1-17 14 6 22-19-12-19 12 6-22-17-14 22-1z"/><path d="M1465 245l9 25 26 1-20 16 7 26-22-14-22 14 7-26-20-16 26-1z"/><path d="M1370 650l7 19 20 1-16 13 5 20-16-11-17 11 6-20-16-13 20-1z"/></g>
            @elseif ($theme['id'] === 'jul')
                <g class="theme-art-pine"><path d="M0 0l80 65-20-2 84 82-27-4 88 94-35-7 83 104-34-9 80 116-299 0z"/><path d="M1600 0l-80 65 20-2-84 82 27-4-88 94 35-7-83 104 34-9-80 116h299z"/><path d="M0 740l70 40-22 2 78 56-24 3 78 65H0z"/><path d="M1600 740l-70 40 22 2-78 56 24 3-78 65h180z"/></g>
                <g class="theme-art-ornaments"><circle cx="185" cy="250" r="23"/><circle cx="1415" cy="250" r="23"/><circle cx="115" cy="690" r="17"/><circle cx="1490" cy="650" r="17"/></g>
                <g class="theme-art-stars"><path d="M275 95l8 21 22 1-17 14 6 22-19-12-19 12 6-22-17-14 22-1z"/><path d="M1325 95l8 21 22 1-17 14 6 22-19-12-19 12 6-22-17-14 22-1z"/></g>
            @elseif ($theme['id'] === 'vinter')
                <g class="theme-art-snowflakes"><path d="M100 185v110M45 240h110M62 202l76 76M138 202l-76 76M250 570v145M178 642h144M198 590l104 104M302 590L198 694M1480 210v135M1413 278h135M1432 230l96 96M1528 230l-96 96M1340 670v105M1288 722h104M1303 685l74 74M1377 685l-74 74"/></g>
                <g class="theme-art-frost"><path d="M0 680c80-65 135 30 210-25s130 35 205-35M1600 530c-80-65-135 30-210-25s-130 35-205-35"/></g>
            @elseif ($theme['id'] === 'var')
                <g class="theme-art-branch"><path d="M0 760c110-100 150-215 305-335M0 280c90 30 150 80 270 168M1600 760c-110-100-150-215-305-335"/></g>
                <g class="theme-art-sprouts"><path d="M90 655c-45-41-4-87 38-56-3 35-15 52-38 56z"/><path d="M153 570c-44-43-2-88 39-55-4 34-16 51-39 55z"/><path d="M228 488c-43-40 0-86 40-53-5 33-18 49-40 53z"/><path d="M85 310c-42-42 0-85 40-53-6 34-19 49-40 53z"/><path d="M1545 655c45-41 4-87-38-56 3 35 15 52 38 56z"/><path d="M1472 570c44-43 2-88-39-55 4 34 16 51 39 55z"/></g>
                <g class="theme-art-flowers"><circle cx="130" cy="420" r="13"/><circle cx="105" cy="420" r="13"/><circle cx="117" cy="397" r="13"/><circle cx="117" cy="443" r="13"/><circle cx="1462" cy="485" r="13"/><circle cx="1437" cy="485" r="13"/><circle cx="1449" cy="462" r="13"/><circle cx="1449" cy="508" r="13"/></g>
            @elseif ($theme['id'] === 'sommer')
                <g class="theme-art-sun"><circle cx="1450" cy="145" r="62"/><path d="M1450 48v-35M1450 277v-35M1353 145h-35M1582 145h-35M1382 77l-25-25M1518 213l25 25M1518 77l25-25M1382 213l-25 25"/></g>
                <g class="theme-art-clouds"><path d="M52 230c5-39 56-47 75-15 35-31 91-2 77 39H52z"/><path d="M1280 650c5-39 56-47 75-15 35-31 91-2 77 39h-152z"/></g>
                <g class="theme-art-waves"><path d="M0 790c70-36 125 36 195 0s125 36 195 0M1210 820c70-36 125 36 195 0s125 36 195 0"/></g>
                <g class="theme-art-summer-leaves"><path d="M145 520c58-48 104 10 62 67-46 1-72-23-62-67z"/><path d="M215 605c57-44 100 14 59 68-45-1-70-26-59-68z"/><path d="M1455 420c58-48 104 10 62 67-46 1-72-23-62-67z"/></g>
            @endif
        </svg>
    </div>
@endif
