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

Standardplasseringen kan overstyres med `ADMIN_STATISTICS_SUMMARY_PATH`. Lenken til detaljrapporten kan overstyres med `ADMIN_GOACCESS_REPORT_URL`; standard er `/statistikk/`.
