# Statistikksammendrag for adminportalen

Laravel åpner ikke SQLite-databasen. `scripts/generate_admin_statistics.py` leser historikken skrivebeskyttet og skriver et begrenset JSON-sammendrag atomisk til `storage/app/statistikk/admin-summary.json`. JSON-filen inneholder ingen IP-adresser; IP-tabellen brukes bare til å telle unike besøkende.

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

Alle logger som dekker de siste 60 dagene, inkludert relevante `.gz`-filer, må oppgis hver gang fordi perioden gjenoppbygges. Eksporter jobbens eksisterende `ADMIN_IP` som miljøvariabel; ikke skriv verdien i repositoryet eller kommandolinjelogger. Kontroller de faktiske loggstiene og at loggformatet er Nginx combined-format før produksjonsjobben endres.

Innsamleren teller bare vellykkede `GET`-forespørsler, avviser kjente roboter/skannere og tekniske stier, og fjerner query-parametere. Kjente rutealiaser samles, mens `/tv`, `/print` og `/print-ilseng` forblir separate. Ukjente, vellykkede offentlige HTML-stier beholder URL-en som navn. Nye botmønstre eller tekniske endepunkter må legges til innsamleren når resten av statistikkjobben får tilsvarende klassifiseringsendringer.

Standardplasseringen kan overstyres med `ADMIN_STATISTICS_SUMMARY_PATH`. Lenken til detaljrapporten kan overstyres med `ADMIN_GOACCESS_REPORT_URL`; standard er `/statistikk/`.
