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

## Søkemotorer og utskrift

HTML-utskriftsruter svarer med `X-Robots-Tag: noindex, follow`. De står ikke i
`robots.txt`, fordi søkemotorer må kunne hente en side for å oppdage `noindex`.
Dette påvirker ikke Fangenytt-PDF-er eller vanlige innholdssider.

### Sesjonscookie i produksjon

Sesjonscookien er `Secure` som standard når `APP_ENV=production`; sett likevel
`SESSION_SECURE_COOKIE=true` eksplisitt i produksjonens `.env`. Lokale miljøer
som kjøres over HTTP kan beholde `SESSION_SECURE_COOKIE=false`. Etter endring
av `.env` eller konfigurasjon må Laravels konfigurasjonscache tømmes med
`php artisan optimize:clear` før responsheadere kontrolleres.

## Fangenytt-arkiv

Fangenytt-metadata lagres varig i tabellen `fangenytt_issues`; `config/fangenytt.php`
er kun historisk seed-kilde for nr. 1–18. PDF-er ligger utenfor Git i
`storage/app/fangenytt/`, og forsider i `storage/app/fangenytt/covers/`. Laravel-rutene
`/fangenytt/{nummer}/pdf` og `/fangenytt/{nummer}/cover` leverer kun utgaver med
status `published`. Arkivet påvirkes ikke av `git pull`, deployer eller
`php artisan optimize:clear`, forutsatt at `storage/` er en vedvarende katalog.

Ved første deploy av databaseendringen kjøres følgende, i denne rekkefølgen:

```bash
sudo -u forge composer dump-autoload --no-scripts --no-dev --optimize
sudo -u forge php artisan migrate --force
sudo -u forge php artisan db:seed --class='Database\Seeders\FangenyttIssueSeeder' --force
```

Seedingen er idempotent og registrerer nr. 1–18 uten å flytte, laste ned eller
endre eksisterende lokale PDF-er og covers.

`database/seeds/` inneholder både eldre globale seedere og namespacede
`Database\Seeders`-klasser. Composer har derfor både historisk classmap og en
eksplisitt PSR-4-mapping for den namespacede strukturen. Autoloaderen må
regenereres etter deploy når en ny seederklasse er lagt til.

Importer en ny utgave manuelt uten kodeendring:

```bash
sudo -u forge php artisan fangenytt:import 19 "https://www.fangeforeningen.no/...pdf" --edition="1/2027" --published-at="2027-01-15" --source=manual
```

Kommandoen godtar bare HTTPS-lenker fra de eksplisitt godkjente
Fangeforeningen-vertsnavnene i konfigurasjonen. Den oppretter utgaven som `pending`,
validerer HTTP-respons og PDF-signatur, lagrer den lokale PDF-en, genererer cover med
`pdftoppm`, og setter først deretter status til `published`. Feil gir `failed`, rydder
opp lokale mellomfiler og gjør aldri utgaven offentlig. `poppler-utils` må være
installert på produksjonsserveren.

`php artisan fangenytt:sync` og `php artisan fangenytt:covers` fungerer fortsatt for
publiserte databaseutgaver. Den siste brukes for å opprette manglende covers uten ny
nedlasting; `--force` regenererer eksisterende covers.

En fremtidig e-post- eller nettsteddetektor skal ikke importere filer selv. Den skal
bare sende kandidatdata (`number`, `original_url`, valgfri `edition`, `published_at`
og `source`, for eksempel `email` eller `website`) til `FangenyttImporter`. Ingen
e-postintegrasjon eller automatisk schedulerjobb er aktivert ennå.

Ved rollback av denne migrasjonen fjernes bare metadata-tabellen; lokale PDF-er og
covers slettes ikke. En ny `migrate` etterfulgt av seed-kommandoen registrerer nr.
1–18 på nytt. Metadata for senere dynamisk importerte utgaver må ved behov gjenopprettes
fra databasebackup.

## Historisk dokumentasjon

`PROJECT_ANALYSIS.md` er et historisk øyeblikksbilde og kan inneholde utdaterte opplysninger. Kontroller alltid gjeldende kode, tester og konfigurasjon.
