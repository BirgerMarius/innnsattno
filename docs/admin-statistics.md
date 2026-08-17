# Statistikksammendrag for adminportalen

Adminportalen leser bare det begrensede JSON-sammendraget; Laravel åpner aldri historikkdatabasen eller Nginx-loggene. Produksjon bruker schema 3, som skiller antatt menneskelig bruk fra kjent automatisert/teknisk og uklassifisert trafikk. JSON-filen inneholder ingen IP-adresser.

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

Innsamleren må alltid få hele loggsettet som dekker vinduet, ikke bare dagens aktive logg. Den erstatter aggregerte rader innenfor det glidende 60-dagersvinduet og fjerner eldre rader fra sine egne tabeller. Generatoren eksporterer deretter nøyaktig 60 kalenderdager i JSON-feltet `daily`, inkludert null-dager.

Hvis eldre Nginx-logger mangler, betyr nuller tidlig i 60-dagersvinduet ikke nødvendigvis at det var null trafikk; de kan være resultat av ufullstendig logghistorikk. Dette må vurderes ved feilsøking og før tall sammenlignes over tid.

## Konfigurasjon og ekskluderinger

`ADMIN_IP` brukes i produksjonsjobben for å holde administratorens hjemme- og testtrafikk utenfor statistikken. `STATISTICS_EXCLUDED_IPS` brukes for ekstra eksplisitte ekskluderinger og inneholder den kjente automatiserte kilden `85.25.43.170`.

Ikke skriv administratorens faktiske IP, andre private verdier, hemmeligheter eller tokenverdier i repositoryet. De hører bare hjemme i `/root/innsatt-statistikk/statistikk.conf` eller tjenestens sikre driftsmiljø.

Uptime Kuma klassifiseres normalt som `monitoring`. Hvis den kommer fra samme IP som `ADMIN_IP`, får eksplisitt IP-ekskludering prioritet og trafikken vises som `excluded`. Det er ønsket: administrator-, test- og overvåkingstrafikk skal ikke inngå i brukerstatistikken.

## Schema 3 og adminvisningen

Schema 3 har tre hoveddeler:

- `periods`: ferdiggenererte aggregater for `1`, `7` og `30` dager.
- `daily`: 60 ferdiggenererte daglige aggregater med nøkkeltall og siderangering.
- `top_pages`: siderangering for periodene `1`, `7` og `30` dager.

Hver daglig oppføring inneholder antatte reelle sidevisninger, antatte besøkende, estimerte økter, utskriftssider, mest brukte sider/funksjoner, kjent automatisert/teknisk trafikk og uklassifisert trafikk. Ingen IP-adresser eksporteres.

`/adm` støtter **I dag**, **Siste 7 dager**, **Siste 30 dager** og **Velg dato**. En datoforespørsel (`traffic_date=YYYY-MM-DD`) leser bare den allerede genererte `daily`-oppføringen; den analyserer aldri Nginx-logger under en webrequest. Datoer håndteres med Europe/Oslo som grunnlag.

## Klassifisering

- `human`: vellykket GET mot offentlig HTML-side med nok øktbevis til å være antatt menneskelig.
- `bot`: kjente crawler- og bot-User-Agents, blant annet ClaudeBot, Googlebot, bingbot, Amazonbot, Applebot og AhrefsBot.
- `monitoring`: Uptime Kuma og andre monitor-User-Agents.
- `scanner`: kjente sikkerhetsskannerstier, mislykkede tekniske forespørsler og raske bønnetid-enumereringer.
- `excluded`: trafikk fra eksplisitt konfigurerte IP-er, inkludert administratortrafikk.
- `other`: uklassifisert trafikk. Dette er ikke automatisk kjent teknisk trafikk.

Kjent automatisert/teknisk trafikk er nøyaktig summen av `bot`, `monitoring`, `scanner` og `excluded`. `other` holdes separat fordi den kan inneholde legitime enkeltstående sidevisninger, for eksempel fra en nettleser som allerede har CSS og bilder i cache. `single_page_candidates` viser hvor mange slike enkeltstående sidekandidater som finnes i uklassifisert trafikk.

En økt er foreløpig samme IP + User-Agent med maksimalt 30 minutters inaktivitet. Besøkende og økter er derfor estimater fra anonymisert logganalyse; flere brukere kan dele offentlig IP.

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

- `schema_version` er `3`.
- `daily` inneholder 60 oppføringer.
- `periods` inneholder `1`, `7` og `30`.
- `admin-summary.json` inneholder ingen IP-adresser.
- `daily_page_ip_stats` har ingen menneskelige siderader for eksplisitt ekskluderte IP-er.
- `innsatt-statistikk.service` har `0/SUCCESS`, og `innsatt-statistikk.timer` er aktiv og planlagt hvert 15. minutt.

Standardplasseringen kan overstyres i utvikling med `ADMIN_STATISTICS_SUMMARY_PATH`. Lenken til detaljrapporten kan overstyres med `ADMIN_GOACCESS_REPORT_URL`; standard er `/statistikk/`.
