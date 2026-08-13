<figure class="learning-figure learning-figure--{{ $figure }}">
    @if($figure === 'budget')
        <svg viewBox="0 0 640 170" role="img" aria-labelledby="budget-figure-title budget-figure-desc">
            <title id="budget-figure-title">Et budsjett fordeler inntekt i ulike utgifter</title><desc id="budget-figure-desc">Inntekt trekkes fra faste og variable utgifter. Det som er igjen kan brukes senere eller spares.</desc>
            <path class="learning-figure-line" d="M145 85h45m120 0h45m120 0h35"/><path class="learning-figure-arrow" d="m185 77 12 8-12 8m145-16 12 8-12 8m145-16 12 8-12 8"/>
            <rect x="15" y="45" width="130" height="80" rx="8"/><text x="80" y="76">Inntekt</text><text x="80" y="101">19 000 kr</text>
            <rect x="210" y="45" width="100" height="80" rx="8"/><text x="260" y="72">Faste</text><text x="260" y="96">utgifter</text>
            <rect x="375" y="45" width="100" height="80" rx="8"/><text x="425" y="72">Variable</text><text x="425" y="96">utgifter</text>
            <rect x="510" y="45" width="115" height="80" rx="8"/><text x="567" y="72">Penger</text><text x="567" y="96">igjen</text>
        </svg>
        <figcaption>Et budsjett gir pengene en rekkefølge: først det nødvendige, deretter det som varierer.</figcaption>
    @elseif($figure === 'scam')
        <svg viewBox="0 0 640 190" role="img" aria-labelledby="scam-figure-title scam-figure-desc">
            <title id="scam-figure-title">Faresignaler i en mistenkelig melding</title><desc id="scam-figure-desc">En mobilmelding med tre faresignaler: hastverk, lenke og forespørsel om opplysninger.</desc>
            <rect x="205" y="12" width="230" height="165" rx="18"/><rect x="222" y="40" width="196" height="110" rx="5"/><path d="M275 70h120m-120 22h90m-90 22h105"/>
            <path class="learning-figure-line" d="M120 65h130m265 0h-60M130 128h120m265 0h-70"/><circle cx="103" cy="65" r="18"/><text x="103" y="70">!</text><circle cx="103" cy="128" r="18"/><text x="103" y="133">!</text><circle cx="534" cy="65" r="18"/><text x="534" y="70">!</text>
            <text x="18" y="36">Hastverk</text><text x="18" y="169">Be om kode</text><text x="475" y="36">Lenke</text>
        </svg>
        <figcaption>Stopp opp når en melding prøver å få deg til å handle fort, trykke på en lenke eller dele opplysninger.</figcaption>
    @elseif($figure === 'sleep')
        <svg viewBox="0 0 640 180" role="img" aria-labelledby="sleep-figure-title sleep-figure-desc">
            <title id="sleep-figure-title">En enkel døgnrytme</title><desc id="sleep-figure-desc">Et døgn med morgenlys og aktivitet, roligere kveld og søvn om natten.</desc>
            <path class="learning-figure-line" d="M80 100h480"/><circle cx="130" cy="100" r="27"/><path d="M130 61v-12m0 102v-12m39-39h12m-102 0H67m91-28 8-8m-72 72 8-8"/><path d="M360 70c24 0 38 18 38 38 0 25-21 42-47 42-29 0-48-17-48-42 0-20 13-38 37-38 8 0 14 2 20 6Z"/>
            <text x="70" y="155">Morgenlys og aktivitet</text><text x="270" y="155">Roligere kveld</text><text x="450" y="155">Søvn</text><path class="learning-figure-arrow" d="m260 92 14 8-14 8m151-16 14 8-14 8"/>
        </svg>
        <figcaption>Døgnrytmen påvirkes av hva du gjør gjennom hele dagen, ikke bare av det som skjer ved leggetid.</figcaption>
    @else
        <svg viewBox="0 0 640 190" role="img" aria-labelledby="gps-figure-title gps-figure-desc">
            <title id="gps-figure-title">GPS fra satellitter til telefon</title><desc id="gps-figure-desc">Tre satellitter sender signaler til en telefon som beregner en posisjon.</desc>
            <circle cx="105" cy="38" r="16"/><circle cx="320" cy="28" r="16"/><circle cx="535" cy="38" r="16"/><path d="M105 54 280 145M320 44v101M535 54 360 145"/><path class="learning-figure-line" d="M45 170q275-45 550 0"/><rect x="286" y="110" width="68" height="50" rx="8"/><path d="M305 122h30m-30 10h30m-15 21h0"/>
            <text x="72" y="88">Satellitt</text><text x="286" y="73">Satellitt</text><text x="502" y="88">Satellitt</text><text x="278" y="184">Beregnet posisjon</text>
        </svg>
        <figcaption>Telefonen sammenligner signaler fra flere satellitter for å beregne hvor den er.</figcaption>
    @endif
</figure>
