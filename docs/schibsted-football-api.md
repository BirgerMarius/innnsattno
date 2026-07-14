# Schibsted/VG SportsNext fotball-API

Sist oppdatert: 2026-07-14.

Dette dokumentet skiller mellom `bekreftet live`, `funnet i eksisterende kode`, `testet og avvist`, `hypotese` og `ukjent`. Bare 2xx-responser teller som bekreftet API-stotte.

## Datakilde

Base-URL:

```text
https://api.sportsnext.schibsted.io/v1/vg
```

En tidligere Codex-sandbox klarte ikke DNS-oppslag. Manuell curl og Laravel-kommandoer fra `docker01`/containeren bekreftet 2026-07-14 at API-et fungerer. Manglende Codex-nettverk var derfor en sandboxbegrensning, ikke bevis pa nedetid i API-et.

## Bekreftet Live

Bekreftet turnering og sesong:

| Felt | Verdi |
| --- | --- |
| Turnering | FIFA World Cup 2026 |
| tournamentId | 19 |
| seasonId | 7767 |
| Verifikasjon | `live_api_from_docker01` |
| Dato | 2026-07-14 |

Disse endepunktene returnerte HTTP 200:

```text
GET /tournaments/seasons/7767/schedule
GET /tournaments/seasons/7767/standings
GET /tournaments/seasons/7767
GET /tournaments/19
GET /tournaments/19/seasons
```

Schedule-responsen inneholder 104 events og disse toppnivafeltene:

- `events`
- `participants`
- `tournament`
- `tournamentSeason`
- `countries`

Standings-responsen inneholder disse toppnivafeltene:

- `standings`
- `tournament`
- `tournamentSeason`
- `participants`
- `countries`

`participants`-data folger med i bade schedule og standings.

## Testet Og Avvist

Disse eksplisitte kandidatstiene returnerte HTTP 404. De er avviste kandidater, ikke systemfeil:

```text
GET /tournaments/seasons/7767/participants
GET /tournaments/seasons/7767/teams
GET /tournaments/seasons/7767/statistics
GET /tournaments/seasons/7767/lineups
```

Separate participants- og teams-endepunkter finnes altsa ikke pa de testede sesongstiene, selv om participants-data finnes innebygd i schedule og standings.

## Funnet I Eksisterende Kode

- `app/Http/Controllers/FootballController.php` bruker World Cup-sesong `7767`.
- `app/Services/PremierLeagueService.php` bruker Schibsted-data med standard `SCHIBSTED_PREMIER_LEAGUE_TOURNAMENT_ID=3` og `SCHIBSTED_PREMIER_LEAGUE_SEASON_ID=9186`.
- `config/services.php` inneholder `schibsted_sports.base_url`, timeout, cache, retry, katalogsti og kjente sesong-ID-er.

## Premier League

Premier League 2026/27 er bekreftet live i Schibsted/VG SportsNext API-et.

| Felt | Verdi |
| --- | --- |
| Turnering | Premier League |
| tournamentId | 3 |
| seasonId | 9186 |
| Sesong | 2026/27 |
| Verifikasjon | `live_api_from_docker01` |
| Dato | 2026-07-14 |

Disse endepunktene returnerte HTTP 200 og gyldig JSON:

```text
GET /tournaments/seasons/9186
GET /tournaments/seasons/9186/schedule
GET /tournaments/seasons/9186/standings
GET /tournaments/3
GET /tournaments/3/seasons
```

Observerte data:

- Schedule inneholder 380 events.
- Standings inneholder 1 tabell med 20 lag.
- `/tournaments/3/seasons` inneholder 34 sesonger.
- `participants` folger med i bade schedule og standings.
- Schedule har toppnivafeltene `events`, `participants`, `tournament`, `tournamentSeason` og `countries`.
- Standings har toppnivafeltene `standings`, `tournament`, `tournamentSeason`, `participants` og `countries`.

Brukseksempler:

```bash
php artisan football:schibsted-explore \
  --path=/tournaments/seasons/9186 \
  --summary \
  --no-cache

php artisan football:schibsted-explore \
  --path=/tournaments/seasons/9186/schedule \
  --summary \
  --no-cache

php artisan football:schibsted-explore \
  --path=/tournaments/seasons/9186/standings \
  --summary \
  --no-cache

php artisan football:schibsted-explore \
  --path=/tournaments/3/seasons \
  --summary \
  --no-cache
```

## Turneringskatalog

Maskinlesbar katalog ligger her:

```text
storage/app/reference/schibsted-football-tournaments.json
```

World Cup 2026-oppforingen bruker:

- `provider: Schibsted SportsNext`
- `sport: football`
- `tournament_id: 19`
- `current_season_name: World Cup 2026`
- `current_season_id: 7767`
- `verification_method: live_api_from_docker01`
- `verified_at: 2026-07-14`
- `source: live_verification`

Capability-/endpoint-status skiller mellom `confirmed`, `rejected_404` og ukjente/null-verdier. Premier League skal ha `verification_status: confirmed`, `tournament_id: 3` og `current_season_id: 9186`.

## Explorer

Explorer brukes til ett kontrollert kall mot en eksplisitt relativ sti:

```bash
php artisan football:schibsted-explore \
  --path=/tournaments/seasons/7767 \
  --summary

php artisan football:schibsted-explore \
  --path=/tournaments/19/seasons \
  --summary
```

Nyttige valg:

- `--method=GET` eller `--method=HEAD`
- `--show-body --max-body=4000` for avkortet body
- `--save --output=world-cup-2026-schedule.json` for rarrespons og metadata
- `--timeout=10`
- `--no-cache`

Standardoppsummeringen skriver struktur, felt og antall. Repetitive lister og ID-felt komprimeres med totalantall, et lite antall eksempler og markering nar flere treff finnes.

## Probe

Probe brukes til et lite, eksplisitt sett kandidatstier:

```bash
php artisan football:schibsted-probe \
  --season=7767 \
  --tournament=19 \
  --profile=season \
  --delay=500 \
  --timeout=10
```

Profiler:

- `season`: tester kjente og naerliggende sesongkandidater rundt World Cup 2026.
- `event`: tester et lite sett kamp-/eventkandidater. Krever `--event`.
- `participant`: tester et lite sett deltaker-/lagkandidater. Krever `--participant`.
- `all-safe`: kombinerer de sma listene. Krever alle relevante ID-er.

Probe genererer ikke numeriske ID-er, bruteforcer ikke og har forsinkelse mellom kall.

Resultattolkning:

- `2xx`: bekreftet kandidat.
- `3xx`: redirect, registreres separat og teller ikke som bekreftet API-stotte.
- `401`, `403`, `404`: avvist kandidat, ikke systemfeil.
- `5xx`: ukjent/API-feil.
- Nettverksfeil eller timeout: ukjent fra kjoremiljoet, ikke bevis pa at API-et er nede.

Lagrede probe-rapporter havner under:

```text
storage/app/reference/schibsted/probes/
```

## Hypoteser

Mulige katalogendepunkter som `/tournaments`, `/tournaments?sport=football` og `/sports` er hypoteser til de er testet med explorer-klienten. De skal ikke brukes til a fylle Premier League-ID-er uten 2xx-respons som faktisk inneholder korrekt turnerings-/sesongdata.

## Forbehold

Schibsted/VG-data, turneringsnavn, lagnavn, logoer og relaterte medier kan vaere underlagt vilkar, opphavsrett, varemerker eller lisensavtaler. Offentlig bruk av storre mengder data eller grafiske assets bor avklares for publisering.
