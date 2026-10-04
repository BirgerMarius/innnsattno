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

Codex har generell tillatelse til å kjøre nødvendige tester, kontrollkommandoer, byggesteg og lokale restarter uten å be om godkjenning for hver enkelt kommando.

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

## Fangenytt-arkiv

Fangenytt-PDF-er lagres utenfor Git i `storage/app/fangenytt/`. Laravel-ruten
`/fangenytt/{nummer}/pdf` leverer bare utgaver som er registrert i
`config/fangenytt.php`, og åpner dem inline i nettleseren. Arkivet påvirkes ikke
av `git pull`, deployer eller `php artisan optimize:clear`, forutsatt at
`storage/` er en vedvarende katalog på serveren.

For å legge inn en ny utgave manuelt:

1. Legg utgaven øverst i `config/fangenytt.php` med `number`, eventuell
   `edition`/dato, `original_url` og nøyaktig `local_file` på formen
   `fangenytt-{nummer}.pdf`.
2. Kjør `php artisan fangenytt:sync` på produksjonsserveren. Eksisterende filer
   hoppes over; bruk bare `--force` dersom en lokal fil bevisst skal erstattes.
   Ved ny nedlasting genereres også en forside.
3. Etter deploy, eller for å opprette manglende forsider til eksisterende PDF-er
   uten ny nedlasting, kjør `php artisan fangenytt:covers`. Bruk `--force` bare
   når alle forsider bevisst skal genereres på nytt.
4. Kontroller `/fangenytt`, åpne `/fangenytt/{nummer}/pdf`, og kontroller
   `/fangenytt/{nummer}/cover` for en utgave med generert forside.

Kommandoen henter bare konfigurerte utgaver, validerer HTTP-status og PDF-signatur,
og fortsetter med neste utgave hvis én nedlasting feiler. Den oppdager ikke nye
utgaver automatisk. Forsider lagres utenfor Git i `storage/app/fangenytt/covers/`
som komprimerte JPEG-bilder, og genereres én gang med Popplers `pdftoppm`.
Produksjonsserveren må ha pakken `poppler-utils` installert; den inngår også i
prosjektets lokale Docker-image.

## Historisk dokumentasjon

`PROJECT_ANALYSIS.md` er et historisk øyeblikksbilde og kan inneholde utdaterte opplysninger. Kontroller alltid gjeldende kode, tester og konfigurasjon.
