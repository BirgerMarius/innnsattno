<?php

namespace App\Http\Controllers;

class ActivityController extends Controller
{
    /**
     * Activity data is kept together so a new printable activity only needs one entry here.
     */
    private array $activities = [
        'sju-er-kabal' => [
            'title' => '7-er-kabal', 'kind' => 'Kabal med kort', 'players' => '1 person',
            'equipment' => 'Én vanlig kortstokk med 52 kort. Ta bort jokerne.',
            'intro' => 'Målet er å legge alle kortene i fire rekker, én rekke for hver farge.',
            'setup' => ['Bland kortene.', 'Legg de fire syverne med billedsiden opp ved siden av hverandre.', 'Legg resten i en bunke med billedsiden ned.'],
            'turns' => ['Snu ett kort fra bunken.', 'Kan kortet legges ved en syver eller et kort som allerede ligger på bordet, legg det ut. Ellers legg det i en egen avkastbunke.', 'I hver fargerekke legges kortene i tallrekkefølge ut fra syveren: 6, 5, 4 … på venstre side og 8, 9, 10 … på høyre side. Knekt kommer etter 10, så dame og konge.', 'Når bunken er tom, snur du avkastbunken og bruker den som ny bunke.'],
            'ending' => 'Du vinner når alle 52 kortene ligger i fargerekkene. Stopp hvis du ønsker, eller når du ikke får lagt ut flere kort etter en hel gjennomgang av bunken.',
            'example' => 'Du snur spar 6. Spar 7 ligger på bordet, så du legger spar 6 rett til venstre for den. Senere kan spar 5 legges til venstre for spar 6.',
            'optional' => 'Valgfritt: Avtal at bunken bare kan gås gjennom tre ganger for en større utfordring.',
        ],
        'vri-atter' => [
            'title' => 'Vri åtter', 'kind' => 'Kortspill', 'players' => '2–4 personer',
            'equipment' => 'Én vanlig kortstokk med 52 kort. Ta bort jokerne.',
            'intro' => 'Bli først kvitt alle kortene dine. Åtter kan brukes for å velge ny farge.',
            'setup' => ['Bland kortene. Del ut 7 kort til hver. Legg resten som trekkebunke.', 'Snu øverste kort fra trekkebunken som start på avkastbunken. Er det en åtter, legg den nederst og snu et nytt kort.', 'Spilleren til venstre for den som delte ut, begynner.'],
            'turns' => ['Legg ett kort som har samme farge (hjerter, ruter, kløver eller spar) eller samme verdi som øverste kort i avkastbunken.', 'En åtter kan legges når som helst. Den som legger åtteren sier hvilken farge som skal gjelde videre.', 'Har du ikke et lovlig kort, trekk ett kort. Kan det kortet spilles, kan du legge det med én gang. Hvis ikke, går turen videre.', 'Når trekkebunken er tom, bland avkastbunken unntatt øverste kort og lag en ny trekkebunke.'],
            'ending' => 'Den som først ikke har flere kort på hånden, vinner runden.',
            'example' => 'Øverst ligger ruter 10. Du kan legge en hvilken som helst ruter eller en 10-er. Legger du kløver 8 og sier «spar», må neste spiller legge spar eller en åtter.',
            'optional' => 'Valgfritt: Spill flere runder og gi ett poeng til vinneren i hver runde.',
        ],
        'krig' => [
            'title' => 'Krig', 'kind' => 'Kortspill', 'players' => '2 personer',
            'equipment' => 'Én vanlig kortstokk med 52 kort. Ta bort jokerne.',
            'intro' => 'Vinn kortene ved å legge kort med høyest verdi. Ess er høyest.',
            'setup' => ['Bland kortene og del dem likt, 26 til hver. Ikke se på kortene.', 'Hver spiller legger sin bunke med billedsiden ned foran seg.'],
            'turns' => ['Begge snur øverste kort samtidig. Høyest kort vinner begge kortene og legger dem nederst i sin bunke. Rekkefølgen kortene legges nederst i betyr ikke noe.', 'Rekkefølgen er 2 lavest, deretter 3–10, knekt, dame, konge og ess høyest.', 'Blir det likt, blir det krig: Begge legger ett kort ned og deretter ett kort opp. Høyest av de nye åpne kortene vinner alle kortene på bordet.', 'Er det fortsatt likt, gjentas krigen. Har en spiller for få kort til en ny krig, vinner den andre spilleren.'],
            'ending' => 'Du vinner når du har alle kortene, eller når den andre ikke kan gjennomføre en krig.',
            'example' => 'Du snur konge, den andre snur 8. Du vinner begge. Hvis begge snur dame, legger begge ett kort ned og ett opp for å avgjøre krigen.',
            'optional' => 'Valgfritt: Stopp etter en avtalt tid. Den med flest kort vinner.',
        ],
        'svarteper' => [
            'title' => 'Svarteper', 'kind' => 'Kortspill', 'players' => '2–5 personer',
            'equipment' => 'Én vanlig kortstokk. Ta bort alle kløver knekt og jokerne. Spar knekt er Svarteper.',
            'intro' => 'Lag par og unngå å sitte igjen med Svarteper til slutt.',
            'setup' => ['Bland kortene og del ut alle kortene så jevnt som mulig.', 'Alle ser på egne kort og legger straks ned par med samme verdi, for eksempel to 9-ere eller to damer. Fargen har ingen betydning.'],
            'turns' => ['Spilleren til venstre for den som delte ut, holder kortene sine som en vifte med baksiden mot neste spiller.', 'Neste spiller trekker ett tilfeldig kort uten å se forsiden først.', 'Har spilleren nå et par, legges paret ned. Turen går videre til venstre.', 'Når en spiller ikke har kort igjen, er spilleren ute av spillet og har klart seg.'],
            'ending' => 'Spillet er slutt når alle par er lagt ned. Den som sitter igjen med spar knekt (Svarteper), taper.',
            'example' => 'Du trekker en ruter 4 og har allerede en spar 4. Legg begge 4-erne ned med én gang.',
            'optional' => 'Valgfritt: Bruk en joker som Svarteper i stedet. Ta da bort én tilfeldig knekt slik at det fortsatt blir ett kort uten par.',
        ],
        'fisk' => [
            'title' => 'Fisk', 'kind' => 'Kortspill', 'players' => '2–4 personer',
            'equipment' => 'Én vanlig kortstokk med 52 kort. Ta bort jokerne.',
            'intro' => 'Samle flest mulig firere: fire kort med samme verdi.',
            'setup' => ['Bland kortene. Ved 2–3 spillere får alle 7 kort. Ved 4 spillere får alle 5 kort.', 'Legg resten som trekkebunke. Den til venstre for den som delte ut, begynner.', 'Se på dine egne kort.'],
            'turns' => ['På din tur spør du én bestemt spiller om en verdi du selv har minst ett kort av, for eksempel «Har du noen damer?»', 'Har spilleren kort av verdien, må alle slike kort gis til deg. Da får du spørre igjen.', 'Har spilleren ingen, sier spilleren «Fisk». Du trekker ett kort fra bunken. Treffer du verdien du spurte om, får du spørre igjen; ellers går turen videre.', 'Får du fire like kort, legg dem åpent foran deg som et stikk. Har du ingen håndkort når det er din tur, trekk ett kort før du spør.'],
            'ending' => 'Når både trekkebunken og alle hender er tomme, teller dere stikkene. Flest firere vinner.',
            'example' => 'Du har hjerter dame og spør Ali om damer. Ali har spar dame og ruter dame og må gi begge til deg. Du har nå tre damer og kan spørre igjen.',
            'optional' => 'Valgfritt: Ved uavgjort deler spillerne seieren.',
        ],
        'sjakk' => [
            'title' => 'Sjakk', 'kind' => 'Brettspill', 'players' => '2 personer',
            'equipment' => 'Sjakkbrett og 16 brikker i hver farge.',
            'intro' => 'Målet er å sette motstanderens konge i sjakk matt: Kongen er truet og kan ikke slippe unna.',
            'setup' => ['Legg brettet slik at hver spiller har et lyst hjørne nederst til høyre.', 'Bakre rad, fra venstre: tårn, springer, løper, dronning, konge, løper, springer, tårn. Dronningen står på sin egen farge.', 'Sett åtte bønder på raden foran. Hvit begynner.'],
            'turns' => ['Flytt én brikke. Du kan ikke flytte til et felt der egen konge blir truet.', 'Konge: ett felt i alle retninger. Dronning: så langt den vil rett, skrått eller sidelengs. Tårn: så langt den vil rett opp, ned eller til siden. Løper: så langt den vil skrått. Springer: i en L, to felt én vei og ett til siden; den kan hoppe over brikker. Bonde: ett felt framover, eller to fra startfeltet hvis begge feltene er ledige. Bonde slår ett felt skrått framover.', 'Når en brikke flyttes til et felt med en motstanders brikke, tas den brikken av brettet.'],
            'special' => ['Sjakk: En konge er i sjakk når en motstanders brikke kan slå den neste trekk. Spilleren må straks flytte kongen, slå den angripende brikken eller blokkere angrepet.', 'Sjakk matt: Kongen står i sjakk og ingen lovlig løsning finnes. Den som gir matt, vinner.', 'Patt: Spilleren som skal trekke er ikke i sjakk, men har ingen lovlige trekk. Partiet er uavgjort.', 'Rokade: Én gang kan kongen flytte to felt mot et tårn, og tårnet hopper over til feltet ved siden av kongen. Det er bare lov hvis kongen og tårnet ikke har flyttet, ingen brikker står mellom dem, og kongen ikke står i, går gjennom eller ender i sjakk.', 'En passant: Har en bonde nettopp gått to felt fram og passert et felt der motstanderens bonde kunne ha slått den, kan den slås som om den bare gikk ett felt. Det må gjøres på neste trekk.', 'Bondeforvandling: Når en bonde når bakerste rad, byttes den straks til dronning, tårn, løper eller springer i samme farge. Dronning er vanligst.'],
            'ending' => 'Partiet avsluttes med sjakk matt, patt eller når begge er enige om remis. Remis kan også avtales hvis samme stilling gjentas tre ganger.',
            'example' => 'En hvit bonde står på e5. En svart bonde går fra d7 til d5. På hvits neste trekk kan bonden fra e5 slå den svarte bonden ved å flytte skrått til d6: en passant.',
            'optional' => 'Valgfritt: Bruk sjakklokke først når begge kjenner reglene.',
        ],
        'dam' => [
            'title' => 'Dam', 'kind' => 'Brettspill', 'players' => '2 personer',
            'equipment' => 'Dambrettp med 64 felt og 12 brikker i hver farge. Et sjakkbrett kan brukes.',
            'intro' => 'Ta alle motstanderens brikker eller steng dem slik at de ikke kan flytte.',
            'setup' => ['Bruk bare de mørke feltene. Legg brettet slik at hver spiller har et lyst hjørne nederst til høyre.', 'Hver spiller legger sine 12 brikker på de tre nærmeste radene med mørke felt.', 'Mørk farge begynner.'],
            'turns' => ['En vanlig brikke flytter ett felt skrått framover til et ledig mørkt felt.', 'Kan du hoppe over en motstanders brikke til et ledig felt rett bak den, må du hoppe og ta brikken av brettet. Å hoppe er obligatorisk.', 'Kan samme brikke hoppe igjen etter et hopp, skal den fortsette å hoppe i samme tur.', 'Når en brikke når bakerste rad, blir den dame. Legg en annen brikke oppå som merke. En dame kan flytte og hoppe skrått både framover og bakover, så langt veien er ledig.'],
            'ending' => 'Du vinner når motstanderen ikke har brikker igjen eller ikke har et lovlig trekk.',
            'example' => 'Din brikke kan hoppe over en motstanders brikke fra c3 til e5, og e5 er ledig. Du må gjøre hoppet og tar brikken du hoppet over av brettet.',
            'optional' => 'Valgfritt: Avtal remis hvis samme stilling gjentas tre ganger.',
        ],
        'backgammon' => [
            'title' => 'Backgammon', 'kind' => 'Brettspill med terninger', 'players' => '2 personer',
            'equipment' => 'Backgammonbrett, 15 lyse og 15 mørke brikker og to vanlige terninger. En kopp til terningene er valgfri.',
            'intro' => 'Flytt alle 15 brikkene rundt brettet og ta dem ut før motstanderen gjør det samme.',
            'setup' => ['Sett opp som i illustrasjonen: For hver farge ligger 2 brikker på punkt 24, 5 på punkt 13, 3 på punkt 8 og 5 på punkt 6. Motstanderens brikker står speilvendt.', 'Hver spiller har sitt hjemland på de seks feltene nærmest seg. Begge kaster én terning; høyest tall begynner og bruker de to tallene som første kast.', 'Begge flytter brikkene mot sitt eget hjemland. På illustrasjonen flytter lys fra 24 ned mot 1; mørk flytter motsatt vei.'],
            'turns' => ['Kast begge terningene og flytt én brikke for hvert tall, enten med to ulike brikker eller samme brikke i to etapper. Du velger rekkefølgen.', 'En brikke kan lande på et tomt punkt, et punkt med egne brikker eller et punkt med nøyaktig én motstanders brikke. To eller flere motstanderbrikker blokkerer punktet.', 'Lander du på én motstanders brikke, slår du den. Legg den på baren i midten. Den som har en brikke på baren, må føre den inn igjen før andre trekk kan gjøres.', 'For å komme inn fra baren bruker du et terningtall til et åpent punkt i motstanderens hjemland. Er begge muligheter blokkert, mister du turen.', 'Dobbelt kast, for eksempel 4 og 4, spilles fire ganger: 4–4 betyr fire flytt på fire felt. Kan du spille ett av tallene, må du gjøre det. Kan bare ett tall brukes, bruk det høyeste.', 'Når alle dine 15 brikker er i eget hjemland, kan du ta dem ut. Et kast kan ta ut en brikke fra punktet med samme tall. Er det ingen brikke der, kan du ta ut den bakerste brikken bare hvis du ikke har noen brikker på høyere punkter.'],
            'ending' => 'Den som først har tatt alle 15 brikkene ut av brettet, vinner. Dette arket bruker vanlig seier uten poengdobling.',
            'example' => 'Du kaster 3 og 5. Du kan flytte én brikke tre felt og en annen fem felt, eller én brikke først tre og deretter fem felt dersom begge mellomlandinger er lovlige.',
            'optional' => 'Valgfritt: Bruk doblingskube og flere poeng først når begge kjenner grunnspillet.',
            'diagram' => 'backgammon',
        ],
    ];

    public function index()
    {
        return view('activities.index', ['activities' => $this->activities]);
    }

    public function show(string $activity)
    {
        return view('activities.show', $this->activityData($activity));
    }

    public function print(string $activity)
    {
        return view('activities.print', $this->activityData($activity));
    }

    private function activityData(string $activity): array
    {
        $activityData = $this->activities[$activity] ?? null;
        abort_unless($activityData, 404);

        return compact('activity', 'activityData');
    }
}
