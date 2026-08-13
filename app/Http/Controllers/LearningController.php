<?php

namespace App\Http\Controllers;

class LearningController extends Controller
{
    /**
     * Prototype data only. This deliberately has no database or admin interface.
     */
    private array $sheets = [
        'hverdagsliv' => [
            'name' => 'Hverdagsliv og samfunn',
            'description' => 'Kunnskap som kan gjøre det lettere å ta valg og bruke tjenester i hverdagen.',
            'icon' => 'wallet',
            'sheets' => [
                'budsjett' => [
                    'title' => 'Et enkelt budsjett',
                    'intro' => 'Et budsjett er en plan for pengene dine. Det gir oversikt før pengene er brukt, og kan gjøre det lettere å unngå ubehagelige overraskelser.',
                    'learn' => ['hva et budsjett er', 'hvordan du kan sette opp en enkel oversikt', 'hva som er viktigst når pengene ikke strekker til'],
                    'sections' => [
                        ['title' => 'Start med det som kommer inn', 'paragraphs' => ['Skriv opp alle inntekter du vanligvis har i løpet av en måned. Det kan være lønn, ytelser eller andre faste beløp. Bruk beløpet du faktisk får utbetalt på konto, altså etter at skatt og andre trekk er gjort.', 'Inntektene er rammen for resten av planen. Når du vet rammen, blir det lettere å se hva du har å bruke uten å måtte gjette fra uke til uke.']],
                        ['title' => 'Del utgiftene i to', 'paragraphs' => ['Faste utgifter er regninger som ofte kommer hver måned, som husleie, strøm, telefon og forsikring. Variable utgifter er ting som varierer, som mat, transport, klær og fritid.', 'Skriv opp de faste utgiftene først. Deretter kan du sette av et realistisk beløp til de variable. Se gjerne på kontoutskrifter eller gamle kvitteringer når du anslår beløpene. Små kjøp teller også når de gjentas mange ganger.']],
                        ['title' => 'Gi pengene en rekkefølge', 'paragraphs' => ['Noen utgifter får større konsekvenser enn andre hvis de ikke blir betalt. Bolig, strøm, mat og nødvendig transport bør derfor stå øverst. Etterpå kommer avtaler og abonnementer som kan være mulig å endre eller si opp.', 'Et budsjett handler ikke om å frata deg alt hyggelig. Det handler om å bestemme hva pengene skal brukes til før de forsvinner av seg selv.']],
                        ['title' => 'Et eksempel for én måned', 'paragraphs' => ['Kari får 19 000 kroner utbetalt. Faste utgifter er 11 500 kroner. Da har hun 7 500 kroner igjen til mat, transport, klær og annet. Deler hun beløpet på fire, er det omtrent 1 875 kroner per uke.', 'En ukeoversikt gjør det lettere å oppdage tidlig dersom pengene er i ferd med å ta slutt. Hvis én uke blir dyrere, kan hun justere litt de neste ukene i stedet for å vente til kontoen er tom.']],
                        ['title' => 'Når regnestykket ikke går opp', 'paragraphs' => ['Se først etter utgifter som kan reduseres, flyttes eller avsluttes. Ta kontakt med den du skylder penger så tidlig som mulig hvis du ikke kan betale i tide. Det er ofte bedre å avtale en løsning enn å la regningen ligge.', 'Du trenger ikke ha et perfekt budsjett. Målet er å få oversikt og justere litt etter litt. Kommunen tilbyr økonomisk rådgivning gjennom NAV for personer som trenger hjelp med gjeld eller en vanskelig økonomisk situasjon.']],
                    ],
                    'box' => ['title' => 'Kort forklart', 'text' => 'Et beløp du setter av til en utgift, er ikke det samme som penger du må bruke. Blir det noe til overs, kan det brukes senere eller spares.'],
                    'figure' => 'budget',
                    'fact' => 'Et budsjett er en plan framover. En kontoutskrift viser hva som allerede har skjedd.',
                    'questions' => ['Hva er forskjellen på en fast og en variabel utgift?', 'Hvorfor bør du bruke beløpet du faktisk får utbetalt, når du lager budsjett?', 'Hvilke utgifter vil du selv sette øverst på listen?', 'Hva kan du gjøre tidlig hvis du ser at en regning blir vanskelig å betale?', 'Se for deg at du har penger igjen til fire uker. Hvordan kan en ukeoversikt hjelpe deg?'],
                    'learnMore' => ['Spør på biblioteket etter bøker om privatøkonomi eller hverdagsøkonomi.', 'NAV kan gi økonomisk rådgivning hvis gjeld eller regninger har blitt vanskelig å håndtere.'],
                ],
                'nettsvindel' => [
                    'title' => 'Stopp opp før du trykker',
                    'intro' => 'Svindlere prøver ofte å få deg til å handle fort. Med noen enkle vaner kan du gjøre det vanskeligere å lure deg.',
                    'learn' => ['hva som er vanlige faresignaler', 'hvordan du kan sjekke en melding uten å ta sjanser', 'hva du gjør hvis noe virker feil'],
                    'sections' => [
                        ['title' => 'Et press er et faresignal', 'paragraphs' => ['Meldinger som sier «svar nå», «kontoen stenges» eller «du har vunnet», vil ofte få deg til å handle før du tenker. Seriøse aktører ber sjelden om passord, kode eller BankID på melding eller telefon.', 'Vær ekstra forsiktig når en melding vekker frykt, hastverk eller stor glede. Svindlere kan late som de er banken, politiet, posten eller en nettbutikk. Navnet på avsenderen er ikke bevis på at meldingen er ekte.']],
                        ['title' => 'Se etter flere faresignaler', 'paragraphs' => ['Dårlig språk, en merkelig nettadresse eller et uventet vedlegg kan være tegn på svindel. Det samme gjelder tilbud som virker for gode til å være sanne. Likevel kan en svindelmelding være godt skrevet og se profesjonell ut.', 'Derfor er det klokt å se på hva meldingen ber deg om å gjøre. Ber den deg dele en kode, betale fort eller flytte penger til en «sikker konto», skal du stoppe opp.']],
                        ['title' => 'Sjekk på en trygg måte', 'paragraphs' => ['Ikke bruk lenken eller telefonnummeret i meldingen. Finn heller virksomhetens vanlige nettsted selv, eller ring et nummer du kjenner fra før. Se nøye på nettadressen: én ekstra bokstav eller et annet endestykke kan være viktig.', 'Spør gjerne en person du stoler på før du deler opplysninger eller penger. Å vente litt er som regel trygt; en ekte virksomhet tåler at du undersøker.']],
                        ['title' => 'Hvis du har gitt fra deg noe', 'paragraphs' => ['Kontakt banken med én gang hvis du har delt BankID, kortopplysninger eller overført penger. Bytt passord der det er nødvendig. Du kan også kontakte politiet for råd eller anmeldelse.', 'Det viktigste er å reagere raskt. Mange opplever svindelforsøk; det er ikke flaut å be om hjelp. Skriv gjerne ned hva som skjedde, når det skjedde og hvem du eventuelt snakket med.']],
                    ],
                    'box' => ['title' => 'Eksempel', 'text' => 'Du får en SMS om at en pakke venter, men at du må betale 19 kroner nå. I stedet for å trykke på lenken kan du gå til transportørens nettsted på egen hånd og sjekke om det faktisk finnes en pakke.'],
                    'figure' => 'scam',
                    'fact' => 'BankID er en personlig elektronisk legitimasjon. Den skal aldri deles med andre, heller ikke noen som sier de ringer fra banken.',
                    'questions' => ['Nevn to faresignaler i en mistenkelig melding.', 'Hvorfor bør du ikke bruke lenken i en uventet melding?', 'Hva betyr det å sjekke en henvendelse på en trygg måte?', 'Hvem kan du kontakte raskt hvis du har delt kort- eller BankID-opplysninger?', 'Hva ville du gjort hvis en melding sa at kontoen din ble stengt i dag?'],
                    'learnMore' => ['Be biblioteket om bøker eller oppslagsverk om digital sikkerhet og svindel.', 'Banken, politiet eller Forbrukerrådet kan gi råd når du er usikker på en henvendelse.'],
                ],
            ],
        ],
        'kropp-og-helse' => [
            'name' => 'Kropp og helse',
            'description' => 'Enkle forklaringer om kroppen, vaner og hva som kan gi mer overskudd.',
            'icon' => 'heart',
            'sheets' => [
                'sovn' => [
                    'title' => 'Søvn: kroppens vedlikeholdstid',
                    'intro' => 'Når du sover, fortsetter kroppen å arbeide. Søvn hjelper blant annet hjernen med å sortere inntrykk og kroppen med å hente seg inn.',
                    'learn' => ['hvorfor søvn betyr noe', 'hva som ofte forstyrrer søvnen', 'hvordan døgnrytmen kan bli mer stabil'],
                    'sections' => [
                        ['title' => 'Søvn er ikke bortkastet tid', 'paragraphs' => ['Under søvn jobber hjernen med minner og inntrykk fra dagen. Kroppen regulerer også flere viktige funksjoner. Etter lite søvn kan det bli vanskeligere å konsentrere seg, være tålmodig og ta gode valg.', 'Behovet er forskjellig fra person til person. Mange voksne fungerer best med rundt sju til ni timer søvn. En dårlig natt er vanlig og betyr ikke nødvendigvis at noe er galt.']],
                        ['title' => 'Kroppen har en indre klokke', 'paragraphs' => ['Døgnrytmen er kroppens egen rytme gjennom døgnet. Den påvirkes blant annet av lys, måltider, aktivitet og når du sover. Lys om morgenen og aktivitet på dagtid forteller kroppen at dagen er i gang.', 'Om kvelden trenger kroppen tid til å skru ned tempoet. Når tidene varierer mye fra dag til dag, kan det bli vanskeligere å kjenne seg trøtt når du ønsker å legge deg.']],
                        ['title' => 'Lag et tydelig skille', 'paragraphs' => ['Prøv å stå opp omtrent på samme tid hver dag, også når natten har vært dårlig. Det er ofte et bedre startpunkt enn å prøve å sove lenge neste morgen. En kort lur kan hjelpe noen, men en lang lur sent på dagen kan gjøre kvelden vanskeligere.', 'Hvis du kan, bruk den siste tiden før leggetid til noe roligere. Sterkt lys, koffein sent på dagen og bekymringer rett før leggetid kan gjøre innsovning vanskeligere.']],
                        ['title' => 'Når tankene går i ring', 'paragraphs' => ['Det hjelper ikke alltid å prøve hardere å sovne. Noen får nytte av å skrive ned det de må huske neste dag, eller puste rolig med litt lengre utpust enn innpust. Målet er ikke å tvinge fram søvn, men å gi kroppen roligere forhold.', 'Snakk med helsepersonell dersom søvnproblemene varer lenge, påvirker hverdagen mye eller henger sammen med smerter, sterk uro eller nedstemthet.']],
                    ],
                    'box' => ['title' => 'Myte eller fakta?', 'text' => 'Myte: Du må alltid få nøyaktig åtte timer søvn. Fakta: Søvnbehov varierer. Det viktigste er hvordan du fungerer over tid, og om rytmen din er noenlunde stabil.'],
                    'figure' => 'sleep',
                    'fact' => 'Du kan ikke alltid «ta igjen» søvn på én natt, men en fast døgnrytme kan over tid gjøre søvnen mer stabil.',
                    'questions' => ['Hva er døgnrytmen?', 'Hvorfor kan lys og aktivitet på dagtid være nyttig for søvnen?', 'Nevn én ting som kan gjøre det lettere å bli trøtt om kvelden.', 'Hvorfor kan det hjelpe å stå opp omtrent til samme tid?', 'Hva kan du prøve hvis tankene går i ring ved leggetid?', 'Når kan det være lurt å snakke med helsepersonell om søvn?'],
                    'learnMore' => ['Spør biblioteket etter bøker om søvn, stress eller gode vaner.', 'Ta opp søvnplager med helsepersonell dersom de varer lenge eller går ut over hverdagen.'],
                ],
            ],
        ],
        'kunnskap-og-teknologi' => [
            'name' => 'Kunnskap og teknologi',
            'description' => 'Hverdagskunnskap om teknologi og naturfenomener som påvirker oss.',
            'icon' => 'spark',
            'sheets' => [
                'gps' => [
                    'title' => 'GPS: slik finner telefonen veien',
                    'intro' => 'GPS gjør det mulig å finne posisjonen din nesten hvor som helst på jorden. Systemet bruker satellitter langt over oss, ikke et kart inni telefonen.',
                    'learn' => ['hva satellitter sender', 'hvorfor telefonen trenger flere signaler', 'hvorfor posisjonen av og til blir unøyaktig'],
                    'sections' => [
                        ['title' => 'Satellitter sender tid', 'paragraphs' => ['GPS-satellitter sender hele tiden signaler med svært nøyaktig klokkeslett og informasjon om hvor satellitten er. Telefonen måler hvor lang tid signalet bruker fram. Siden radiosignaler går med lysets hastighet, kan telefonen regne ut avstanden til satellitten.', 'Telefonen mottar signaler; den trenger ikke å sende et signal til satellitten for å vite omtrent hvor den er. Det er grunnen til at GPS i seg selv ikke krever at telefonen har mobildekning.']],
                        ['title' => 'Flere målinger gir en posisjon', 'paragraphs' => ['Med avstanden til én satellitt vet telefonen at den er et sted på en stor kule rundt satellitten. Med flere satellitter blir området mindre. Dette kalles posisjonsberegning: telefonen finner punktet som passer best med alle avstandene.', 'Vanligvis brukes minst fire satellitter for å beregne posisjon og rette opp små feil i telefonens klokke. Kartappen kombinerer så posisjonen med kartdata og andre sensorer i telefonen.']],
                        ['title' => 'Kart, GPS og internett er ulike ting', 'paragraphs' => ['Et digitalt kart er informasjon om veier, bygninger og steder. GPS forteller omtrent hvor du er. Internett kan laste ned kart raskere, vise trafikk og hjelpe telefonen med en grov startposisjon.', 'Derfor kan en kartapp fungere annerledes når dekningen er dårlig: Telefonen kan fortsatt motta satellittsignaler, men kanskje ikke hente nye kart eller oppdatert trafikk.']],
                        ['title' => 'Hvorfor kan pilen hoppe?', 'paragraphs' => ['Høye bygninger, fjell, tett skog og innendørs bruk kan svekke eller reflektere signaler. Da kan telefonen beregne litt feil. En refleksjon betyr at signalet tar en omvei før det kommer fram.', 'En posisjon er derfor et anslag, ikke alltid et helt nøyaktig punkt. Hvis du skal finne en bestemt inngang eller adresse, kan det være lurt å bruke både kartet, omgivelsene og egne observasjoner.']],
                    ],
                    'box' => ['title' => 'Kort forklart', 'text' => 'GPS er navnet på ett satellittsystem. Mange moderne telefoner bruker også andre systemer samtidig. Flere signaler kan gi en raskere og mer presis posisjon.'],
                    'figure' => 'gps',
                    'fact' => 'GPS er én av flere satellittjenester for navigasjon. Europa har også Galileo, og moderne telefoner kan ofte bruke flere systemer samtidig.',
                    'questions' => ['Hva måler telefonen når den bruker GPS?', 'Hvorfor trenger telefonen signaler fra flere satellitter?', 'Hva er forskjellen på et kart og GPS?', 'Nevn én ting som kan gjøre GPS-posisjonen mindre nøyaktig.', 'Trenger GPS-signalet i seg selv mobildekning?', 'Hvordan kan du dobbeltsjekke en posisjon som virker usikker?'],
                    'learnMore' => ['Spør på biblioteket etter bøker om romfart, satellitter eller hverdags-teknologi.', 'Et atlas eller et kart kan være et godt supplement når du vil forstå steder og avstander.'],
                ],
            ],
        ],
    ];

    public function index()
    {
        return view('learning.index', ['categories' => $this->sheets]);
    }

    public function show(string $category, string $sheet)
    {
        return view('learning.show', $this->sheetData($category, $sheet));
    }

    public function print(string $category, string $sheet)
    {
        return view('learning.print', $this->sheetData($category, $sheet));
    }

    private function sheetData(string $category, string $sheet): array
    {
        $categoryData = $this->sheets[$category] ?? null;
        $sheetData = $categoryData['sheets'][$sheet] ?? null;

        abort_unless($categoryData && $sheetData, 404);

        return compact('category', 'sheet', 'categoryData', 'sheetData');
    }
}
