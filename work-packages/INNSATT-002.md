# Work Package INNSATT-002

## Title

Lag værmeldingsside for Tyristrand og Ringerike fengsel

## Goal

Lag en enkel og oversiktlig værmeldingsside for Ringerike fengsel med værdata for Tyristrand-området.

Undersøk først eksisterende værintegrasjon og vær-API i prosjektet. Gjenbruk eksisterende løsning og konfigurasjon så langt det er praktisk mulig.

Dersom dagens integrasjon ikke gir tilstrekkelige data for en ukes værmelding, kan den suppleres med en stabil og gratis værmeldingstjeneste. Ikke legg API-nøkler eller andre hemmeligheter direkte i kildekoden.

Den nye siden skal være tilgjengelig på `/vaer` og vise:

- tydelig stedsnavn: Tyristrand / Ringerike fengsel
- dagens vær
- temperatur
- værtype og passende værikon
- forventet nedbør når dataene finnes
- vind når dataene finnes
- værmelding for de kommende dagene, opptil én uke
- tidspunkt for når værdataene sist ble oppdatert

Bruk norsk språk og samme visuelle stil som resten av innsatt.no. Siden skal være enkel å lese på både mobil og større skjerm.

På forsiden skal det legges inn en egen knapp med teksten:

Værmelding – Tyristrand/Ringerike Fengsel

Knappen skal ha samme stil, størrelse og oppsett som de øvrige knappene for Ringerike fengsel og lenke til `/vaer`.

Ved feil eller manglende kontakt med værtjenesten skal brukeren få en forståelig norsk melding i stedet for en teknisk feilside.

Ikke endre produksjonsoppsett eller deploy-konfigurasjon.

Før ferdigstillelse skal nedbørsberegningen gjennomgås. Unngå å summere overlappende prognoseperioder fra MET. Bruk helst `next_1_hours` for døgnsummering, med en tydelig og ikke-overlappende fallback dersom én-timesdata mangler.

Legg til en målrettet test av selve WeatherForecastService som bruker en representativ MET-respons og bekrefter at temperatur, vind og nedbør beregnes uten dobbelttelling.

Ikke endre det godkjente visuelle oppsettet på forsiden eller værsiden.

## Files

- routes/web.php
- app/Http/Controllers/WeatherController.php
- app/Services/WeatherForecastService.php
- resources/views/weather/index.blade.php
- resources/views/tv/guide.blade.php
- tests/Feature/WeatherForecastTest.php

Codex kan endre eksisterende værrelaterte filer som oppdages under inspeksjonen, dersom dette er nødvendig for å gjenbruke prosjektets eksisterende integrasjon. Endringene skal begrenses til værfunksjonen, forsidenavigasjonen og relevante tester.

## Acceptance Criteria

- En ny værmeldingsside er tilgjengelig på `/vaer`.
- Siden gjelder Tyristrand og Ringerike fengsel.
- Eksisterende værintegrasjon i prosjektet er undersøkt og gjenbrukt når det er praktisk mulig.
- Siden viser dagens vær og de kommende dagene, opptil én uke.
- Temperatur og værtype vises for hver dag.
- Nedbør og vind vises når datakilden leverer dette.
- Værdataene presenteres på norsk.
- Utformingen harmonerer med resten av innsatt.no.
- Forsiden har en knapp med teksten «Værmelding – Tyristrand/Ringerike Fengsel».
- Knappen har samme stil, størrelse og oppsett som de øvrige Ringerike fengsel-knappene.
- Knappen lenker til `/vaer`.
- Feil fra værleverandøren håndteres med en forståelig norsk melding.
- Ingen API-nøkler eller hemmeligheter hardkodes.
- En målrettet feature-test dekker ruten, værvisningen og kontrollert feilhåndtering.
- Nedbørsberegningen summerer ikke overlappende prognoseperioder.
- En målrettet service-test dekker behandling av en representativ MET-respons.
- Eksisterende funksjonalitet beholdes.
- Den målrettede testen består.

## Quality Gate

Test command:
`docker compose -f compose.local.yaml exec -T innsatt-local php artisan test --without-tty tests/Feature/WeatherForecastTest.php`

Test timeout seconds:
`600`

## Status

completed

## Completion

- Implementation commit: `caeff7a1c5a273a2ba85d1c472eb225e60a2271f` (`Add weather forecast for Ringerike prison`)
- Commit status: pushed to `origin/main`
- Targeted test: passed, 3 tests
- Review: passed with no blocking findings
