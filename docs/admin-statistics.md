# Statistikksammendrag for adminportalen

Laravel åpner ikke SQLite-databasen. `scripts/generate_admin_statistics.py` leser historikken skrivebeskyttet og skriver et begrenset JSON-sammendrag atomisk til `storage/app/statistikk/admin-summary.json`. JSON-filen inneholder ingen IP-adresser; IP-tabellen brukes bare til å telle unike besøkende. Schema 3 skiller antatt menneskelig trafikk fra bot-, overvåkings-, skanner- og øvrig trafikk. Rå forespørsler beholdes som tekniske aggregater i SQLite, men er ikke hovedtallene på `/adm`.

## Produksjonskobling (skal utføres senere)

Kjør generatoren i `/root/innsatt-statistikk/oppdater-rapport.sh` etter at historikkdatabasen er oppdatert, med prosjektets faktiske rotkatalog:

```sh
/usr/bin/python3 /STI/TIL/INNSATT/scripts/generate_admin_statistics.py \
  /root/innsatt-statistikk/historikk.sqlite3 \
  /STI/TIL/INNSATT/storage/app/statistikk/admin-summary.json
```

Bruk samme systembruker som statistikkjobben. Katalogen må kunne skrives av jobben og JSON-filen må kunne leses av PHP-brukeren. Generatoren lager katalogen ved behov, setter filmodus `0640` og erstatter først eksisterende fil etter at en komplett ny fil er synkronisert. Dersom validering eller databasespørring feiler, beholdes forrige gyldige fil.

Databasen må ha `daily_stats`, `daily_page_stats` og `daily_ip_stats`. Skriptet oppdager støttede kolonnenavn og avslutter med en tydelig feil ved ukjent skjema. Kjør kommandoen manuelt én gang før jobbskriptet endres. Ikke bruk `--test-data` i produksjon.

## Alle sider og sidefordelte besøkende

Listen over alle ordinære sider krever tabellen `daily_page_ip_stats`, fordi dagsaggregatet `daily_page_stats` ikke kan telle samme IP bare én gang over en periode. `scripts/collect_page_visitors.py` bygger denne tabellen idempotent fra komplette aktive og roterte Nginx access-logger. Rå IP-er blir kun liggende i SQLite i maksimalt 60 dager og eksporteres aldri til JSON.

Innsamleren skal kjøres etter at de ordinære historikktabellene er oppdatert, men før `generate_admin_statistics.py`:

```sh
/usr/bin/python3 /STI/TIL/INNSATT/scripts/collect_page_visitors.py \
  /root/innsatt-statistikk/historikk.sqlite3 \
  /STI/TIL/AKTIV/access.log /STI/TIL/ROTERTE/access.log.1 \
  --retention-days 60

/usr/bin/python3 /STI/TIL/INNSATT/scripts/generate_admin_statistics.py \
  /root/innsatt-statistikk/historikk.sqlite3 \
  /STI/TIL/INNSATT/storage/app/statistikk/admin-summary.json
```

Alle logger som dekker de siste 60 dagene, inkludert relevante `.gz`-filer, må oppgis hver gang fordi perioden gjenoppbygges. Eksporter jobbens eksisterende `ADMIN_IP` som miljøvariabel dersom administratortrafikk skal utelates. Flere eksplisitte ekskluderinger kan settes som kommaseparerte IP-er i `STATISTICS_EXCLUDED_IPS`, eller med gjentatt `--exclude-ip`; ikke skriv verdier i repositoryet eller kommandolinjelogger. Kontroller de faktiske loggstiene og at loggformatet er Nginx combined-format før produksjonsjobben endres.

Innsamleren klassifiserer først kjente User-Agents (bot/crawler og Uptime Kuma/overvåking), velkjente skannerstier og mislykkede tekniske forespørsler. En sidevisning krever vellykket `GET` mot en offentlig HTML-side. Kandidater blir bare antatt menneskelige når samme IP og User-Agent i en 30-minutters økt også har en annen innholdsside eller laster en statisk ressurs; en enslig `/tv` teller derfor ikke automatisk. Et legitimt enkeltstående treff kan dermed havne i `other`/uklassifisert trafikk, blant annet når nettleseren har ressurser i cache; det merkes aldri som bot eller skanner bare av den grunn. `single_page_candidates` viser hvor stor del av den uklassifiserte trafikken dette gjelder. En rask bønnetid-enumerering med mange ulike mål i samme økt klassifiseres som skanning. Statiske ressurser brukes kun som øktbevis og teller aldri som sidevisninger. Kjente rutealiaser samles, mens `/tv`, `/print` og `/print-ilseng` forblir separate. Ukjente, vellykkede offentlige HTML-stier beholder URL-en som navn.

I schema 3 holdes `traffic_quality.known_automated_technical_requests` adskilt fra `traffic_quality.other`. Det første er nøyaktig summen av `known_bot`, `monitoring`, `scanner` og `excluded`; det andre er uklassifisert trafikk og må ikke omtales som kjent teknisk trafikk.

Schema 3 eksporterer også `daily`, et oppslag med personverntrygge nøkkeltall og siderangering for hver av de siste 60 kalenderdagene, inkludert null-dager. `/adm?traffic_date=YYYY-MM-DD` bruker dette oppslaget med Europe/Oslo som datogrunnlag og har prioritet over `traffic_period`. Ugyldige datoer avvises i grensesnittet; en gyldig fremtidig eller ikke-innsamlet dato viser en tydelig ingen-data-tilstand. Ved hver kjøring gjenoppbygges hele 60-dagersvinduet fra alle aktive og roterte logger som oppgis til innsamleren, og erstatter de samme datoradene atomisk i SQLite før JSON-filen genereres. Eldre dager beholdes ikke i `daily`-eksporten; de faller ut når vinduet flyttes frem.

Standardplasseringen kan overstyres med `ADMIN_STATISTICS_SUMMARY_PATH`. Lenken til detaljrapporten kan overstyres med `ADMIN_GOACCESS_REPORT_URL`; standard er `/statistikk/`.
