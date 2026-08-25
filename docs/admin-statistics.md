# Statistikksammendrag for adminportalen

Adminportalen leser bare det begrensede JSON-sammendraget; Laravel åpner aldri historikkdatabasen eller Nginx-loggene. Ny innsamling bruker schema 4, som skiller anslått menneskelig bruk fra kjent automatisert/teknisk og uklassifisert trafikk, og viser nøyaktig hvilke dager som faktisk har logggrunnlag. JSON-filen inneholder ingen IP-adresser.

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

## Schema 4 og adminvisningen

Schema 4 har tre hoveddeler:

- `periods`: ferdiggenererte aggregater for `1`, `7` og `30` dager.
- `daily`: 60 ferdiggenererte daglige aggregater med nøkkeltall og siderangering.
- `top_pages`: siderangering for periodene `1`, `7` og `30` dager.

Hver daglig oppføring inneholder filtrerte sidevisninger, anslåtte besøksøkter, unike besøksnettverk, utskriftsvisninger, mest brukte sider/funksjoner, kjent automatisert/teknisk trafikk, uklassifisert trafikk og loggdekning. Unike nettverk er ikke personer: et institusjonsnett kan representere flere brukere. Ingen IP-adresser eksporteres.

Forsiden er `/tv` og vises som **Forside**. `/print` er **TV-utskrift Ringerike**, og `/print-ilseng` er **TV-utskrift Ilseng**. En utskriftsvisning betyr bare at utskriftssiden ble åpnet, ikke at en fysisk utskrift ble gjennomført.

`/adm` støtter **I dag**, **Siste 7 dager**, **Siste 30 dager** og **Velg dato**. En datoforespørsel (`traffic_date=YYYY-MM-DD`) leser bare den allerede genererte `daily`-oppføringen; den analyserer aldri Nginx-logger under en webrequest. Datoer håndteres med Europe/Oslo som grunnlag.

## Klassifisering

- `human`: vellykket `GET` med 2xx-svar mot offentlig HTML-side med nok øktbevis til å være anslått menneskelig. Redirects (3xx) og `HEAD` er tekniske forespørsler, ikke sidevisninger.
- `bot`: kjente crawler- og bot-User-Agents, blant annet ClaudeBot, Googlebot, bingbot, Amazonbot, Applebot og AhrefsBot.
- `monitoring`: Uptime Kuma og andre monitor-User-Agents.
- `scanner`: kjente sikkerhetsskannerstier og -queryer (inkludert `wp-json`, `rest_route`, WordPress- og PHP-forsøk), mislykkede tekniske forespørsler og raske bønnetid-enumereringer. En `/tv`-forespørsel inntil to minutter etter et slikt forsøk fra samme IP behandles som mulig redirect-rest; regelen er med hensikt kort for ikke å ramme delte nettverk.
- `excluded`: trafikk fra eksplisitt konfigurerte IP-er, inkludert administratortrafikk.
- `other`: uklassifisert trafikk. Dette er ikke automatisk kjent teknisk trafikk.

Kjent automatisert/teknisk trafikk er nøyaktig summen av `bot`, `monitoring`, `scanner` og `excluded`. `other` holdes separat fordi den kan inneholde legitime enkeltstående sidevisninger, for eksempel fra en nettleser som allerede har CSS og bilder i cache. `single_page_candidates` viser hvor mange slike enkeltstående sidekandidater som finnes i uklassifisert trafikk.

En økt er foreløpig samme IP + User-Agent med maksimalt 30 minutters inaktivitet. Besøkende og økter er derfor estimater fra anonymisert logganalyse; flere brukere kan dele offentlig IP.

## Kontrollert produksjonsoppdatering av root-eide deler

Dette repositoryet endrer ikke `/root/innsatt-statistikk` direkte. Før en driftsansvarlig oppdaterer det root-eide oppsettet:

1. Stans timeren midlertidig og ta en konsistent sikkerhetskopi: `sqlite3 /root/innsatt-statistikk/historikk.sqlite3 '.backup /root/innsatt-statistikk/backups/historikk-YYYYMMDD-HHMM.sqlite3'`. Kontroller med `sqlite3 ... 'PRAGMA integrity_check'`.
2. Ta kopier med datostempel av `statistikk.conf`, `oppdater-rapport.sh` og `lagre-historikk.py`. Ikke legg IP-verdiene fra konfigurasjonen i repositoryet.
3. Oppdater `oppdater-rapport.sh` til å bruke denne revisjonens to scripts, sende både `ADMIN_IP` og alle verdier i `STATISTICS_EXCLUDED_IPS` til samleren, og bruke samme ekskluderingsliste for GoAccess. Hvis installert GoAccess-versjon ikke har en sikker IP-ekskluderingsinnstilling, filtreres loggstrømmen før GoAccess i stedet; dette må verifiseres med en prøvefil før aktivering.
4. Oppdater `lagre-historikk.py` med samme regelsett: bare `GET` med 2xx kan telle som sidevisning, 3xx/HEAD beholdes eventuelt bare som tekniske forespørsler, og administrator-/ekskluderte IP-er skal ikke inn i noen av `daily_*`-tabellene. Den bør importere eller bruke samme klassifiseringsregler som `collect_page_visitors.py`, fremfor en kopi.
5. Kjør jobben manuelt mot en kopi av databasen, kontroller schema 4, manglende IP-adresser i JSON, dekning og en kjent WordPress-skannersekvens. Aktiver deretter timeren igjen og kontroller `systemctl status` etter neste kvarter.

Eldre `daily_stats`, `daily_page_stats` og `daily_ip_stats` kan være filtrert etter eldre regler. Schema 4 bruker derfor bare den nye, klassifiserte samlingen ved visning av sammenlignbare tall; de eldre tabellene skal ikke presenteres som likeverdige historiske mennesketall.

## Første kontrollerte produksjonskjøring

For 17.08.2026 viste schema 3-sammendraget:

| Måling | Antall |
| --- | ---: |
| Antatte reelle sidevisninger | 41 |
| Antatte besøkende | 10 |
| Estimerte økter | 12 |
| Utskriftssider brukt | 4 |
| Kjent automatisert/teknisk trafikk | 1 088 |
| Uklassifiserte forespørsler | 1 318 |
| Enkeltstående sidekandidater | 15 |
| Råforespørsler | 2 447 |

## Driftssjekk

Ved kontroll av en ny generering skal minst følgende være sant:

- `schema_version` er `4` etter at ny samler har kjørt.
- `daily` inneholder bare datoer med faktisk logggrunnlag; perioder viser om de er fullstendige eller delvise.
- `periods` inneholder `1`, `7` og `30`.
- `admin-summary.json` inneholder ingen IP-adresser.
- `daily_page_ip_stats` har ingen menneskelige siderader for eksplisitt ekskluderte IP-er.
- `innsatt-statistikk.service` har `0/SUCCESS`, og `innsatt-statistikk.timer` er aktiv og planlagt hvert 15. minutt.

Standardplasseringen kan overstyres i utvikling med `ADMIN_STATISTICS_SUMMARY_PATH`. Lenken til detaljrapporten kan overstyres med `ADMIN_GOACCESS_REPORT_URL`; standard er `/statistikk/`.
