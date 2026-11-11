# Kontrollert sesongbytte for SportsNext

Hver SportsNext-tjeneste bruker konfigurasjonens sesong-ID som standard. Et valgt
overstyringsvalg lagres i `sportsnext_season_selections`; verken scheduler eller
administrasjon skriver `.env` eller versjonerte konfigurasjonsfiler. Cache-nøkler
inneholder sesong-ID, slik at en gammel fungerende cache beholdes etter bytte.

`sportsnext:season-control` kjører daglig 05:15 Europe/Oslo. Den henter
`/tournaments/{tournamentId}/seasons` og krever før automatisk bytte:

1. `currentSeasonId` er en entydig nyere sesong i samme oversikt.
2. Samme kandidat er observert i to vellykkede kontroller med minst tolv timers mellomrom.
3. Gammel sesongs `endDate` er passert med 14 dagers karenstid.
4. Et nytt hentet gammelt kampoppsett bare har ferdige eller avlyste kamper.
5. Nytt kampoppsett og tabell kan hentes og inneholder events, participants og standings.

Premier League, Eliteserien, OBOS-ligaen, La Liga og Bundesliga kan aktiveres
per turnering i administrasjonen, men står avslått som standard. Champions
League, Europa League, Conference League og Nations League krever manuell
godkjenning: kvalifisering, sluttspill og flerårige sesonger publiseres ikke
nødvendigvis samlet.

API-feil eller mangelfulle data endrer aldri sesongvalget. Administrasjonssiden
viser siste vellykkede kontroll og begrunnelse. En tilbakeføring slår av
automatikk og blokkerer samme kandidat til administrator aktivt slår den på igjen.
Historikk ligger i `sportsnext_season_switches`.

Offentlige sesongtekster leser bare den valgte sesongens vedvarende metadata
eller tilsvarende lokale `last_good`-cache; de gjør ingen API-kall. Før første
vellykkede kontroll vises `Sesong-ID: …` i stedet for et gjettet årstall.
En kandidat påvirker aldri offentlig tekst før den faktisk er valgt.

SportsNext kan levere en planlagt eller feilaktig `endDate`. La Liga sesong-ID
9227 er observert med 2026-08-16–2027-08-17; datoen kommer fra SportsNext og
behandles ikke lokalt. Den brukes bare som varselgrunnlag sammen med 14 dagers
karenstid og komplette gamle/nye kampdata; usikker dato gir derfor ikke bytte.

Ved utrulling: kjør bare `php artisan migrate --force`; ikke kjør `cache:clear`
eller `optimize:clear`, fordi de kan fjerne fungerende datacache. Kontroller så
med `php artisan sportsnext:season-control --competition=premier_league`.
