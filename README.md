# innsatt.no

innsatt.no er en norsk Laravel-nettside med informasjon og praktiske verktøy rettet mot innsatte og ansatte i kriminalomsorgen.

## Teknologi

- Laravel 9
- PHP 8
- Blade og Livewire
- Laravel Mix
- PHPUnit

## Lokal utvikling

Prosjektet har lokal Docker-konfigurasjon i:

- `Dockerfile.local`
- `compose.local.yaml`

Start lokalmiljøet med prosjektets vanlige Docker Compose-kommando. Kontroller gjeldende konfigurasjon og `.env` før oppstart.

## Testing

Kjør relevante målrettede tester under utvikling. Hele testpakken kjøres når endringen kan påvirke flere deler av løsningen eller før større leveranser.

Vanlig testkommando:

```bash
php artisan test
```

## Fengselsuavhengige startsider

Forsidene er `/ringerike`, `/ilseng` og `/kongsvinger`; `/` videresender til
Ringerike. Én felles forsidevisning (`resources/views/tv/guide.blade.php`) og
samme kontrollere brukes for alle. Fengselsspesifikke navn, offisielle lenker,
TV-status, værnøkkel og Bønnetid-ID ligger sentralt i `config/prisons.php`.
Værkoordinater og separate cache-nøkler ligger i `config/services.php`.

Oppdater en felles komponent eller controller én gang, og åpne alle tre
startsider for å verifisere endringen. Ved nytt fengsel: legg først en post i
`config/prisons.php`, en separat værkonfigurasjon/cache-nøkkel i
`config/services.php`, verifiser Bønnetid.no-steds-ID og kanalutvalg, og bruk
deretter `/{slug}/...` i lenker og utskrifter. Fengsel velges alltid fra URL-en;
lokale lenker og utskrifter skal beholde samme prefix, og cache-/monitorerings-
nøkler skal inneholde sluggen. `AGENTS.md` er den korte føringen for fremtidige
endringer.

Kongsvingers offisielle avdelinger har samme besøksadresse, Vardåsveien 190,
2214 Kongsvinger. Værstedet bruker adressepunktet 60.2102457, 11.9851459.
TV-guiden er uttrykkelig under klargjøring til en verifisert kanalliste er
mottatt. Bønnetid.no sitt lokasjonsregister krever tilgang; sett den verifiserte
`BONNETID_KONGSVINGER_LOCATION_ID` før bønnetidene for Kongsvinger åpnes.

## Felles header

Den delte headeren har en dekorativ analog klokke med digital visning. Den bruker
nettleserens innebygde tidssonehåndtering for `Europe/Oslo`, oppdateres fra
faktisk klokkeslett og veksler mellom fire lokale design omtrent hvert 30. sekund.
Den er skjult på smale skjermer og ved utskrift; redusert bevegelse bruker ett
designbytte uten overgang, mens sekundviseren er skjult.

### Temaminiatyrer i admin

`public/img/theme-previews/` inneholder statiske, beskårne bilder av den øvre delen av hver temaforhåndsvisning. De brukes på adminoversikten og blir ikke laget når siden åpnes. Oppdater dem etter endringer i forsiden eller temadesignet ved å starte lokalappen og kjøre `scripts/generate_theme_previews.cjs` med Playwright i en midlertidig container. Eksempel med den lokale Compose-nettverksnavnet:

```bash
docker compose -f compose.local.yaml up -d innsatt-local
docker run --rm --network innsatt-test_default -v "$PWD:/work" -w /work mcr.microsoft.com/playwright:v1.57.0-noble sh -lc 'npm install --no-save --prefix /tmp/playwright playwright@1.57.0 && NODE_PATH=/tmp/playwright/node_modules node scripts/generate_theme_previews.cjs'
```

Dette er kun et utviklingssteg og legger ikke til noen produksjonsavhengighet.

Codex har generell tillatelse til å kjøre nødvendige tester, kontrollkommandoer, byggesteg og lokale restarter uten å be om godkjenning for hver enkelt kommando.

## Tidsfordriv

/tidsfordriv samler ordjakt, sudoku, lokale papir-/skjermoppgaver og spillforslag.
Binærpuslespill, nonogram, tallpyramider og minikryssord er merket Test på
nettsidene. Forsidens Nyhet-merke styres av den faste Oslo-tiden i
config/tidsfordriv.php og varer i 14 dager.
De nye oppgavene ligger i app/Services/TidsfordrivPuzzleBank.php. Hver oppgave
har stabil ID og fasit i samme post, slik at URL-en med ?oppgave=ID alltid gir
identisk skjerm, utskrift og fasit. Legg nye kvalitetssikrede oppgaver til i
den aktuelle typen med unik ID, vanskelighetsgrad, oppgavetilstand og løsning.
Ikke bruk eksterne kryssord eller API-er. Utskrift er
/tidsfordriv/{type}/utskrift?oppgave=ID; fasit velges med &fasit=1.

## Arbeidsregler

Gjeldende prosjektinstruksjoner finnes i:

- `PROJECT.md`
- `context/project-rules.md`

Disse filene er autoritative for arbeidsflyt, testing, Git og deploy.

Viktige regler:

- Gjør minste nødvendige endring.
- Bevar eksisterende arkitektur og urelaterte lokale endringer.
- Work packages er valgfrie og brukes bare for store, risikofylte eller langvarige oppgaver.
- Commit og push utføres bare etter uttrykkelig beskjed.
- Alle Git-commitmeldinger skal være på norsk.
- Produksjonsdeploy utføres separat og bare etter uttrykkelig beskjed.

## Deploy

Produksjonsdeploy skjer med prosjektets separate deployverktøy og er ikke en del av vanlig Codex-implementering. Commit og push innebærer aldri automatisk deploy.

Gjeldende produksjonsmiljø og manuelle serverendringer er dokumentert i
`docs/server-changes.md`. Produksjonsserveren kjører Laravel med debug
deaktivert. Schedulerens registrerte cronjobb kjører hvert minutt som brukeren
`forge`; se driftsloggen for gjeldende kommando og prosjektsti.

### Eksterne datakilder

Kildekontroller for VG TV-guide og SportsNext kjøres av Laravels scheduler;
sidene leser lokal cache og venter ikke på disse kildene. TV-data for begge
fengsler og datovinduet åtte dager tilbake til tretten dager frem hentes omtrent hver time i avgrensede
puljer. SportsNext hentes hvert 30. minutt, og hvert femte minutt rundt kamper.
Ved feil prøves manglende, sentrale data på nytt hvert femte minutt; andre
feilkontroller normalt hvert kvarter. DW-programmet hentes omtrent hver time.
Et opphold på mer enn én time mellom kontroller starter feilserien på nytt;
manglende kontroller er ikke bevis på vedvarende nedetid.

Den gamle døgnrapporten er erstattet av kildevise kritiske varsler. Minst fem
mislykkede kildekontroller over en time kreves normalt, sammen med manglende
eller for gamle data. Helt manglende sentrale data varsles etter tre feil over
minst ti minutter. Standardgrensene for TV/fotball er seks timer, vær tre timer
og nyheter ett døgn; de konfigureres i `config/external_data.php`. Bønnetider og
kalenderdata vurderes etter datodekning: eldre data er brukbare for dagene de
dekker. Bønnetider kontrolleres for i dag og sju dager frem for begge fengslene.
VG, SportsNext, DW, MET, Bønnetid.no, Ringblad, navnedager, Wikipedia/Wikimedia,
Wikidata og aktive nyhetskilder kontrolleres uavhengig av sidebesøk. Et varslet utfall
følges opp høyst én gang i døgnet og avsluttes først etter vellykket kontroll av
alle berørte data. Ingen e-post sendes for kortvarige feil.

Scheduleren må startes av serveren hvert minutt. Den registrerte
produksjonsjobben er:

```bash
* * * * * cd /home/forge/innsatt.no/innnsattno && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

Kjør migreringene før de nye jobbene starter. Cache og låser må være felles for
web- og schedulerprosessene, og begge må kunne skrive til cachelagringen. Ved
flere servere må cache og låser deles mellom dem. Jobbene kjører direkte i
scheduleren og krever ingen ny køarbeider. Ved tom cache bygges dataene gradvis
opp; sider kan vise manglende data inntil første vellykkede henting. Overvåk
selve scheduler/cron separat: manglende kontroller kan ikke bevise kildeutfall.
Kontroller alltid gjeldende crontab på serveren før manuelle endringer.

Etter deploy og migrering kan TV- og fotballdata fylles med
`php artisan external-data:warm`. Kommandoen viser resultat per dato og turnering,
beholder reservedata ved feil og avslutter med status 1 ved feil eller manglende
nødvendige data for i dag/tidligere. Fremtidige TV-programmer kan være upubliserte.
Scheduleren fortsetter med nye forsøk.

Datakopiene ligger i standard-cachelageret, ikke i databasens kontrolltabeller.
`cache:clear` og `optimize:clear` sletter siste brukbare data og kan fjerne låser.
Bruk heller målrettet `config:clear`, `route:clear` eller `view:clear` når det er
disse cachene som må fornyes. Hvis deployverktøyet likevel tømmer datacachen,
kjør `external-data:warm` etter siste tømming. Ved `CACHE_DRIVER=file` må web og
scheduler bruke samme vedvarende `storage/framework/cache/data`, samme
cacheprefiks og ha skrivetilgang. Flere servere trenger felles cache og atomiske
låser. Repositoryet dokumenterer ikke deployverktøyets script eller produksjonens
faktiske cacheoppsett; dette må kontrolleres på serveren.

## Søkemotorer og utskrift

HTML-utskriftsruter svarer med `X-Robots-Tag: noindex, follow`. De står ikke i
`robots.txt`, fordi søkemotorer må kunne hente en side for å oppdage `noindex`.
Dette påvirker ikke Fangenytt-PDF-er eller vanlige innholdssider.

## Nasjonale nyheter på forsiden

KDI- og Sivilombudet-feeder hentes og caches separat, men vises bare når en fersk
sak oppfyller de eksplisitte relevans- og nasjonalitetskriteriene. Forsidens
Ringblad-blokk beskriver derfor kun lokale nyheter; fagforeningsnyheter og den
separate `/nyheter`-siden er uavhengige av dette utvalget.

### Sesjonscookie i produksjon

Sesjonscookien er `Secure` som standard når `APP_ENV=production`; sett likevel
`SESSION_SECURE_COOKIE=true` eksplisitt i produksjonens `.env`. Lokale miljøer
som kjøres over HTTP kan beholde `SESSION_SECURE_COOKIE=false`. Etter endring
av `.env` eller konfigurasjon må Laravels konfigurasjonscache tømmes med
`php artisan config:clear` før responsheadere kontrolleres.

## Fangenytt-arkiv

Fangenytt-metadata lagres varig i tabellen `fangenytt_issues`; `config/fangenytt.php`
er kun historisk seed-kilde for nr. 1–18. PDF-er ligger utenfor Git i
`storage/app/fangenytt/`, og forsider i `storage/app/fangenytt/covers/`. Laravel-rutene
`/fangenytt/{nummer}/pdf` og `/fangenytt/{nummer}/cover` leverer kun utgaver med
status `published`. Arkivet påvirkes ikke av `git pull`, deployer eller
`php artisan optimize:clear`, forutsatt at `storage/` er en vedvarende katalog.

Ved første deploy av databaseendringen kjøres følgende, i denne rekkefølgen:

```bash
sudo -u forge composer dump-autoload --no-scripts --no-dev --optimize
sudo -u forge php artisan migrate --force
sudo -u forge php artisan db:seed --class='Database\Seeders\FangenyttIssueSeeder' --force
```

Seedingen er idempotent og registrerer nr. 1–18 uten å flytte, laste ned eller
endre eksisterende lokale PDF-er og covers.

`database/seeds/` inneholder både eldre globale seedere og namespacede
`Database\Seeders`-klasser. Composer har derfor både historisk classmap og en
eksplisitt PSR-4-mapping for den namespacede strukturen. Autoloaderen må
regenereres etter deploy når en ny seederklasse er lagt til.

Importer en ny utgave manuelt uten kodeendring:

```bash
sudo -u forge php artisan fangenytt:import 19 "https://www.fangeforeningen.no/...pdf" --edition="1/2027" --published-at="2027-01-15" --source=manual
```

Kommandoen godtar bare HTTPS-lenker fra de eksplisitt godkjente
Fangeforeningen-vertsnavnene i konfigurasjonen. Den oppretter utgaven som `pending`,
validerer HTTP-respons og PDF-signatur, lagrer den lokale PDF-en, genererer cover med
`pdftoppm`, og setter først deretter status til `published`. Feil gir `failed`, rydder
opp lokale mellomfiler og gjør aldri utgaven offentlig. `poppler-utils` må være
installert på produksjonsserveren.

`php artisan fangenytt:sync` og `php artisan fangenytt:covers` fungerer fortsatt for
publiserte databaseutgaver. Den siste brukes for å opprette manglende covers uten ny
nedlasting; `--force` regenererer eksisterende covers.

En fremtidig e-post- eller nettsteddetektor skal ikke importere filer selv. Den skal
bare sende kandidatdata (`number`, `original_url`, valgfri `edition`, `published_at`
og `source`, for eksempel `email` eller `website`) til `FangenyttImporter`. Ingen
e-postintegrasjon eller automatisk schedulerjobb er aktivert ennå.

Ved rollback av denne migrasjonen fjernes bare metadata-tabellen; lokale PDF-er og
covers slettes ikke. En ny `migrate` etterfulgt av seed-kommandoen registrerer nr.
1–18 på nytt. Metadata for senere dynamisk importerte utgaver må ved behov gjenopprettes
fra databasebackup.

## Historisk dokumentasjon

`PROJECT_ANALYSIS.md` er et historisk øyeblikksbilde og kan inneholde utdaterte opplysninger. Kontroller alltid gjeldende kode, tester og konfigurasjon.

## Adminstatistikk fra tilgangslogger

`scripts/collect_page_visitors.py` klassifiserer komplette aktive og roterte
access-logger og lagrer bare aggregerte sider, datoer og nettverksidentiteter i
SQLite. Klassifiserer v8 holder Fangenytt-cover-ruten
`/fangenytt/{nummer}/cover` utenfor HTML-sidevisninger, også når URL-en har
query-parametere. Den undersøker dessuten ethvert glidende to-minuttersvindu i
en IP+User-Agent-økt for raske, brede siderunder; det er ikke en
cookie-bekreftet brukerøkt.

Kjør begge trinnene med alle relevante, overlappfrie aktive og roterte logger:

```bash
sudo -u forge python3 scripts/collect_page_visitors.py storage/app/statistikk/historikk.sqlite3 /var/log/nginx/innsatt-access.log /var/log/nginx/innsatt-access.log.1.gz
sudo -u forge python3 scripts/generate_admin_statistics.py storage/app/statistikk/historikk.sqlite3 storage/app/statistikk/admin-summary.json
```

## Ukentlig statistikkrapport

Laravel leser bare `admin-summary.json`; den åpner aldri Nginx-loggene eller den
root-eide SQLite-filen. Loggjobben og JSON-generatoren må derfor fortsatt kjøres
hvert kvarter, også rett etter midnatt. Scheduleren forsøker rapporten hver mandag
fra 00:05 (Europe/Oslo) og venter til JSON-en er regenerert etter ukeslutt med
komplett søndagsdekning. Den tar ikke igjen gamle uker ved første deploy.

Konfigurer uten hemmeligheter i driftsmiljøet:

```dotenv
WEEKLY_STATISTICS_REPORT_ENABLED=true
WEEKLY_STATISTICS_REPORT_RECIPIENT=innsatt@innsatt.no
```

Eksisterende `MAIL_*`/Brevo-oppsett brukes uendret. Rapporten registreres per
kalenderuke og låses under sending. Ved transportfeil prøves den igjen neste femte
minutt, og den markeres først sendt når transporten svarer uten feil. Et uklart
nettverkssvar fra Brevo kan i sjeldne tilfeller ha akseptert e-posten før klienten
ser feilen; da finnes en gjenværende risiko for dobbeltutsending ved retry.

Forhåndsvisning sender aldri e-post:

```bash
php artisan statistics:weekly-report:preview 2026-10-12 --output=/tmp/ukesrapport.html
```

Kontrollert, uttrykkelig testsending (påvirker ikke ordinær sendestatus):

```bash
php artisan statistics:weekly-report:test-send 2026-10-12 --to=innsatt@innsatt.no
```

Sjekk alltid hvilke faktiske `access_log`-filer nettstedet bruker før kjøring,
for eksempel med `sudo nginx -T`. Ikke kopier rålogger til repositoryet.
