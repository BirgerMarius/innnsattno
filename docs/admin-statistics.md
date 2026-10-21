# Statistikksammendrag for adminportalen

Adminportalen leser bare det begrensede JSON-sammendraget; Laravel åpner aldri historikkdatabasen eller Nginx-loggene. Ny innsamling bruker schema 5 med klassifiserer versjon 8, som skiller anslått menneskelig bruk fra kjent automatisert/teknisk og uklassifisert trafikk, og viser nøyaktig hvilke dager som faktisk har logggrunnlag. JSON-filen inneholder ingen IP-adresser.

## Produksjonsarkitektur

| Del | Produksjonsplassering |
| --- | --- |
| Applikasjonsrepo | `/home/forge/innsatt.no/innnsattno` |
| Statistikkjobb | `/root/innsatt-statistikk/oppdater-rapport.sh` |
| Jobbkonfigurasjon | `/root/innsatt-statistikk/statistikk.conf` |
| Historikkdatabase | `/root/innsatt-statistikk/historikk.sqlite3` |
| Admin-sammendrag | `/home/forge/innsatt.no/innnsattno/storage/app/statistikk/admin-summary.json` |
| Nginx-logg | `/var/log/nginx/innsatt.no-access.log`, `.1` og roterte `.gz`-filer |

`innsatt-statistikk.service` kjører jobben som `root`. Etter vellykket generering skal JSON-filen eies av `forge:www-data` og ha modus `0640`, slik at PHP kan lese den. Generatoren skriver først en komplett midlertidig fil og erstatter deretter den gamle atomisk; ved feil beholdes sist gyldige JSON-fil.

`innsatt-statistikk.timer` starter tjenesten hvert 15. minutt, på `:00`, `:15`, `:30` og `:45`, med `Persistent=true`. Timeren skal være aktiv etter endringer. Jobben er manuelt og via systemd verifisert med `0/SUCCESS`.

## Kjørerekkefølge

`oppdater-rapport.sh` laster `/root/innsatt-statistikk/statistikk.conf` og kjører i denne rekkefølgen:

1. `/root/innsatt-statistikk/lagre-historikk.py` oppdaterer eksisterende råhistorikk i `daily_stats`, `daily_page_stats` og `daily_ip_stats`.
2. Jobben bygger en liste med aktiv logg, `.1` og relevante roterte `.gz`-logger som dekker minst 60 dager. Listen dedupliseres før den sendes til innsamleren; dette er nødvendig fordi `.1` ellers også kan matches av et mønster som `.[0-9]`.
3. `scripts/collect_page_visitors.py` kjøres med historikkdatabasen, de unike loggfilene og `--retention-days 60`.
4. `scripts/generate_admin_statistics.py` leser databasen skrivebeskyttet og skriver `admin-summary.json`.
5. Jobben setter eier `forge:www-data` og modus `0640` på den nye JSON-filen.

Innsamleren må alltid få hele loggsettet som dekker vinduet, ikke bare dagens aktive logg. Den erstatter bare datoer som faktisk forekommer i de analyserte loggene, registrerer disse i `daily_statistics_coverage`, og fjerner rader eldre enn retention fra sine egne tabeller. Dette hindrer at gyldig historikk slettes når roterte logger forsvinner. Schema 4 eksporterer bare observerte datoer; manglende dager betyr «ukjent», ikke bekreftet null trafikk.

## Konfigurasjon og ekskluderinger

`ADMIN_IP` brukes i produksjonsjobben for å holde administratorens hjemme- og testtrafikk utenfor statistikken. `STATISTICS_EXCLUDED_IPS` brukes for ekstra eksplisitte ekskluderinger og inneholder den kjente automatiserte kilden `85.25.43.170`.

Ikke skriv administratorens faktiske IP, andre private verdier, hemmeligheter eller tokenverdier i repositoryet. De hører bare hjemme i `/root/innsatt-statistikk/statistikk.conf` eller tjenestens sikre driftsmiljø.

Uptime Kuma klassifiseres normalt som `monitoring`. Hvis den kommer fra samme IP som `ADMIN_IP`, får eksplisitt IP-ekskludering prioritet og trafikken vises som `excluded`. Det er ønsket: administrator-, test- og overvåkingstrafikk skal ikke inngå i brukerstatistikken.

### Videresendinger for podkastanbefalingen

`/podkast/betjenten-og-psyken/les-mer` og `/podkast/betjenten-og-psyken/hor` er applikasjonsstyrte `302`-videresendinger med faste mål-URL-er. Nginx logger forespørslene som vanlige access-loggoppføringer, og GoAccess tar som standard med `3xx`-statuskoder i rapportens forespørsels- og statusoversikter. De er ikke menneskelige sidevisninger i det begrensede admin-sammendraget: innsamleren klassifiserer bare vellykkede `GET`-svar med `2xx` som sidekandidater.

Det er ingen Nginx-endring nødvendig så lenge nettstedets vanlige `access_log` fortsatt skriver alle statuskoder til `/var/log/nginx/innsatt.no-access.log`. Ved produksjonsoppdateringen skal driftsansvarlig kontrollere at `/root/innsatt-statistikk/oppdater-rapport.sh` ikke filtrerer bort `3xx`-linjer før GoAccess, og at GoAccess ikke startes med en `--ignore-status=302`-regel eller en sti-ekskludering som dekker `/podkast/betjenten-og-psyken/`. Hvis en slik filtrering finnes, skal den fjernes for disse rutene og rapporten regenereres fra de samme Nginx-loggene.

## Schema 5 og adminvisningen

Schema 5 har fire hoveddeler:

- `periods`: ferdiggenererte aggregater for `1`, `7` og `30` dager.
- `daily`: 60 ferdiggenererte daglige aggregater med nøkkeltall og siderangering.
- `top_pages`: siderangering for periodene `1`, `7` og `30` dager.
- `calendar_weeks`: fire eksakte, avsluttede mandag–søndag-uker med dedupliserte nettverk, alle menneskelige sider og utskriftsåpninger per fengsel. Denne delen brukes av ukesrapporten.

Hver daglig oppføring inneholder filtrerte sidevisninger, anslåtte besøksøkter, unike besøksnettverk, utskriftsvisninger, mest brukte sider/funksjoner, kjent automatisert/teknisk trafikk, uklassifisert trafikk og loggdekning. Unike nettverk er ikke personer: et institusjonsnett kan representere flere brukere. Ingen IP-adresser eksporteres.

Forsiden er `/tv` og vises som **Forside**. `/print` er **TV-utskrift Ringerike**, og `/print-ilseng` er **TV-utskrift Ilseng**. En utskriftsvisning betyr bare at utskriftssiden ble åpnet, ikke at en fysisk utskrift ble gjennomført.

`/adm` støtter **I dag**, **Siste 7 dager**, **Siste 30 dager** og **Velg dato**. En datoforespørsel (`traffic_date=YYYY-MM-DD`) leser bare den allerede genererte `daily`-oppføringen; den analyserer aldri Nginx-logger under en webrequest. Datoer håndteres med Europe/Oslo som grunnlag.

## Klassifisering

- `human`: vellykket `GET` med 2xx-svar mot minst to **ulike** offentlige HTML-sider i samme IP + User-Agent-økt. Redirects (3xx) og `HEAD` er tekniske forespørsler, ikke sidevisninger. CSS, bilder, Referer og gjentatte åpninger av samme side kan ikke alene kvalifisere en økt.
- `bot`: kjente crawler- og bot-User-Agents, blant annet ClaudeBot, Googlebot, GoogleOther, bingbot, Amazonbot, Applebot og AhrefsBot, og den presise, selvdeklarerte adresseskraper-identifikatoren `GO kevincobain2000/email_extractor`. Det er ikke en regel for generisk «Go», gamle nettlesere eller manglende Referer.
- `monitoring`: Uptime Kuma og andre monitor-User-Agents.
- `scanner`: kjente sikkerhetsskannerstier og -queryer (inkludert `wp-json`, `rest_route`, WordPress- og PHP-forsøk), mislykkede tekniske forespørsler og raske bønnetid-enumereringer. En `/tv`-forespørsel inntil to minutter etter et slikt forsøk fra samme IP behandles som mulig redirect-rest; regelen er med hensikt kort for ikke å ramme delte nettverk. Klassifiserer versjon 5 markerer dessuten hele IP + User-Agent-økten som automatisert når enten (a) minst 10 vellykkede offentlige HTML-forespørsler mot minst 8 ulike sider skjer innen 2 minutter, eller (b) minst to feilbehandlede absolutte lenker (`/https:/…` eller `/mailto:…`) forekommer sammen med minst fire vellykkede offentlige HTML-forespørsler innen 5 minutter. Klassifiserer versjon 7 gjør det samme når minst to tydelige private-fil-prober (`.git`, `.env`, WordPress-/XML-RPC-, phpMyAdmin- eller PHPUnit-stier) forekommer i den samme IP + User-Agent-økten; dermed følger også innblandede, vellykkede sider og utskriftssider samme øktklassifisering. Én tilfeldig 404 er ikke nok.
- `other`: `/web.config` er en eksplisitt teknisk konfigurasjonssti, ikke en Laravel-rute eller fil i dette prosjektet. Den blir derfor aldri kandidat til menneskelig sidevisning, selv om Nginx svarer med applikasjonens 200-side. Avgrensningen er med hensikt eksakt og påvirker ikke legitime nedlastinger eller andre filendelser. Klassifiserer versjon 6 flytter dessuten bare `/tidsfordriv`-visninger til uavklart når samme IP + User-Agent-økt har minst to komplette Sudoku-utskriftsløkker innen fem minutter: `GET /` med 3xx, `GET /tv` med 2xx, `GET /tidsfordriv` med 2xx, vellykket `POST /tidsfordriv/sudoku/print` 5–25 sekunder senere, og en ny `GET /` med 3xx innen 60 sekunder. Starten av hver løkke må være høyst to minutter før POST-en. Mellomliggende ressurskall ignoreres. Andre sider, enkeltforsøk, mislykkede POST-er, normal retur direkte til `/tidsfordriv` og TV-utskrifter berøres ikke. De omklassifiserte visningene blir `other` og teller som uavklarte enkeltstående sidekandidater; de beholdes ikke i menneskelige sidevisninger eller nettverk.

Bare menneskeklassifiserte sidevisninger skrives til `daily_page_ip_stats`. Dermed kan en omklassifisert `/tidsfordriv`-visning ikke telle både som menneskelig og uavklart, og den bidrar ikke til rangering eller unike menneskelige nettverk. Generatoren grupperer deretter per `(path, ip)` for hele valgt periode før den teller unike nettverk, så samme nettverk på flere dager teller én gang per side/funksjon i perioden.
- `excluded`: trafikk fra eksplisitt konfigurerte IP-er, inkludert administratortrafikk.

Kjent automatisert/teknisk trafikk er nøyaktig summen av `bot`, `monitoring`, `scanner` og `excluded`. `other` holdes separat fordi den kan inneholde legitime enkeltstående sidevisninger, for eksempel fra en nettleser som allerede har CSS og bilder i cache. `single_page_candidates` viser hvor mange slike enkeltstående sidekandidater som finnes i uklassifisert trafikk.

En økt er foreløpig samme IP + User-Agent med maksimalt 30 minutters inaktivitet. Automatikk i én slik økt omklassifiserer ikke andre User-Agents eller senere økter fra samme IP. Besøkende og økter er derfor estimater fra anonymisert logganalyse; flere brukere kan dele offentlig IP.

## Omklassifisering etter ny klassifiserer

`daily_page_ip_stats` og `daily_traffic_classification_stats` inneholder bare aggregater, ikke tidspunkt, User-Agent eller rekkefølge. De er derfor ikke tilstrekkelige til å bruke versjon 7 på gammel historikk. For å omklassifisere må statistikkjobben kjøres på nytt med de komplette, dedupliserte Nginx-loggene for hver ønsket dag. Samleren erstatter bare dager som faktisk finnes i det angitte loggsettet; den sletter ikke øvrige dager eller råhistorikken. `daily_statistics_coverage.classifier_version` blir 7 for regenererte dager, slik at sammenligning bare skjer mellom fullt dekkede dager med samme regelsett.

## Kontrollert produksjonsoppdatering av root-eide deler

Dette repositoryet endrer ikke `/root/innsatt-statistikk` direkte. Før en driftsansvarlig oppdaterer det root-eide oppsettet:

1. Stans timeren midlertidig og ta en konsistent sikkerhetskopi: `sqlite3 /root/innsatt-statistikk/historikk.sqlite3 '.backup /root/innsatt-statistikk/backups/historikk-YYYYMMDD-HHMM.sqlite3'`. Kontroller med `sqlite3 ... 'PRAGMA integrity_check'`.
2. Ta kopier med datostempel av `statistikk.conf`, `oppdater-rapport.sh` og `lagre-historikk.py`. Ikke legg IP-verdiene fra konfigurasjonen i repositoryet.
3. Oppdater `oppdater-rapport.sh` til å bruke denne revisjonens to scripts, sende både `ADMIN_IP` og alle verdier i `STATISTICS_EXCLUDED_IPS` til samleren, og bruke samme ekskluderingsliste for GoAccess. Hvis installert GoAccess-versjon ikke har en sikker IP-ekskluderingsinnstilling, filtreres loggstrømmen før GoAccess i stedet; dette må verifiseres med en prøvefil før aktivering.
4. Oppdater `lagre-historikk.py` med samme regelsett: bare `GET` med 2xx kan telle som sidevisning, 3xx/HEAD beholdes eventuelt bare som tekniske forespørsler, og administrator-/ekskluderte IP-er skal ikke inn i noen av `daily_*`-tabellene. Den bør importere eller bruke samme klassifiseringsregler som `collect_page_visitors.py`, fremfor en kopi.
5. Kjør jobben manuelt mot en kopi av databasen, kontroller schema 4, manglende IP-adresser i JSON, dekning og en kjent WordPress-skannersekvens. Aktiver deretter timeren igjen og kontroller `systemctl status` etter neste kvarter.

Eldre `daily_stats`, `daily_page_stats` og `daily_ip_stats` kan være filtrert etter eldre regler. Schema 4 bruker derfor bare den nye, klassifiserte samlingen ved visning av sammenlignbare tall; de eldre tabellene skal ikke presenteres som likeverdige historiske mennesketall.

### Manuell produksjonsprosedyre for versjon 7

Kommandoene nedenfor skal kjøres av driftsansvarlig på produksjonsserveren. De gjør ingen Nginx-endring og blokkerer ingen IP-adresser. Stopp timeren før sikkerhetskopien og regenereringen, og gi den bare tilbake etter både database- og Laravel-kontroll. Vanlig `git pull` oppdaterer programkoden, men regenererer **ikke** historiske aggregater.

```bash
release_repo=/home/forge/innsatt.no/innnsattno
sudo systemctl cat innsatt-statistikk.service innsatt-statistikk.timer
sudo sed -n '1,240p' /root/innsatt-statistikk/oppdater-rapport.sh
sudo awk -F= '/^(ADMIN_IP|STATISTICS_EXCLUDED_IPS)=/{print $1"=<satt>"}' /root/innsatt-statistikk/statistikk.conf

cd "$release_repo"
sudo -u forge git pull --ff-only origin main
sudo systemctl stop innsatt-statistikk.timer
sudo install -d -m 0700 /root/innsatt-statistikk/backups
sudo sqlite3 /root/innsatt-statistikk/historikk.sqlite3 ".backup /root/innsatt-statistikk/backups/historikk-$(date +%Y%m%d-%H%M%S).sqlite3"
sudo sqlite3 /root/innsatt-statistikk/historikk.sqlite3 'PRAGMA integrity_check'

# Kontroller at jobben bruker dette repoets v7-samler og generator, og at
# logglisten dekker hele perioden som skal regenereres (aktive, .1 og .gz).
sudo grep -F "$release_repo/scripts/collect_page_visitors.py" /root/innsatt-statistikk/oppdater-rapport.sh
sudo grep -F "$release_repo/scripts/generate_admin_statistics.py" /root/innsatt-statistikk/oppdater-rapport.sh
sudo /root/innsatt-statistikk/oppdater-rapport.sh

sudo sqlite3 /root/innsatt-statistikk/historikk.sqlite3 "SELECT date, classifier_version FROM daily_statistics_coverage ORDER BY date DESC LIMIT 60;"
sudo -u forge test -r "$release_repo/storage/app/statistikk/admin-summary.json"
cd "$release_repo"
sudo -u forge php artisan tinker --execute='echo app(App\Services\AdminStatisticsSummary::class)->read() ? "admin-summary OK\n" : "admin-summary INVALID\n";'
sudo systemctl start innsatt-statistikk.timer
sudo systemctl status --no-pager innsatt-statistikk.timer innsatt-statistikk.service
```

Hvis de to `grep`-kontrollene ikke treffer, skal jobben først oppdateres slik at den peker på denne revisjonens scripts og fortsatt sender `ADMIN_IP` og hver `STATISTICS_EXCLUDED_IPS`-verdi til samleren. Kjør deretter hele jobben mot de dedupliserte, komplette roterte loggene for ønsket historisk vindu; det er dette som erstatter historikkaggregatene med versjon 7.

## Første kontrollerte produksjonskjøring

For 17.08.2026 viste schema 3-sammendraget:

| Måling | Antall |
| --- | ---: |
| Antatte reelle sidevisninger | 41 |
| Antatte besøkende | 10 |
| Estimerte økter | 12 |
| Åpninger av utskriftssider | 4 |
| Kjent automatisert/teknisk trafikk | 1 088 |
| Uklassifiserte forespørsler | 1 318 |
| Enkeltstående sidekandidater | 15 |
| Råforespørsler | 2 447 |

## Ukesrapportens datagrunnlag

Den root-eide `innsatt-statistikk.timer` kjører generatoren hvert kvarter. Når
den kjører etter at søndag 25.10.2026 er ferdig, eksporterer schema 5 nøkkelen
`calendar_weeks.2026-10-19` for 19.–25. oktober. Laravel-scheduleren forsøker
første ordinære e-post mandag 26.10.2026 kl. 00:05 Europe/Oslo. Den sender bare
når JSON-filen er generert etter `26.10.2026 00:00` og alle syv dager har
dekning; hvis 00:00-jobben ikke er ferdig, forsøker den videre hvert femte
minutt mandag. Dette forutsetter fortsatt at `oppdater-rapport.sh` peker på
repoets `scripts/generate_admin_statistics.py`, som kontrollert i
produksjonsprosedyren over.

## Driftssjekk

Ved kontroll av en ny generering skal minst følgende være sant:

- `schema_version` er `5`, og `daily_statistics_coverage.classifier_version` er `8` etter at ny samler har kjørt.
- `daily` inneholder bare datoer med faktisk logggrunnlag; perioder viser om de er fullstendige eller delvise.
- `periods` inneholder `1`, `7` og `30`.
- `admin-summary.json` inneholder ingen IP-adresser.
- `daily_page_ip_stats` har ingen menneskelige siderader for eksplisitt ekskluderte IP-er.
- `innsatt-statistikk.service` har `0/SUCCESS`, og `innsatt-statistikk.timer` er aktiv og planlagt hvert 15. minutt.

Standardplasseringen kan overstyres i utvikling med `ADMIN_STATISTICS_SUMMARY_PATH`. Lenken til detaljrapporten kan overstyres med `ADMIN_GOACCESS_REPORT_URL`; standard er `/statistikk/`.
