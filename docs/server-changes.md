# Produksjonsdrift

Dette er gjeldende, korte driftsveiledning for innsatt.no. Den versjonerte
PowerShell-menyen er [tools/windows/innsatt.ps1](../tools/windows/innsatt.ps1).
Historiske serverendringer starter under «Historikk» og er ikke en deployoppskrift.

## Vanlig deploy

1. Kontroller at riktig commit er pushet til `origin/main`, og at arbeidsområdet
   er rent for den aktuelle endringen.
2. Start `innsatt` på administratør-PC-en og velg **3 – Deploy innsatt.no til
   produksjon**. Bekreft med `j`. Menyen kjører `innsatt-deploy` via SSH-aliaset
   `innsatt-prod`.
3. Les hele resultatet. En feilstatus betyr at deployen ikke er godkjent.
4. Kontroller etterpå `https://innsatt.no/ringerike`, `/ilseng` og
   `/kongsvinger`, inkludert en aktuell fengselsspesifikk lenke når den berørte
   endringen krever det.

Vanlig deploy er tilstrekkelig for kode- og konfigurasjonsendringer. Ikke kjør
`cache:clear` eller `optimize:clear` som del av rutinen: applikasjonsoptimalisering
er noe annet enn datacachene for eksterne kilder. Deployen skal bevare de siste
brukbare eksterne dataene.

## Når eksterne data må oppdateres

Velg **9 – Oppdater cache/data** i samme meny bare når data faktisk må fornyes.
Velg ett fengsel for dets TV-guide, lokale nyheter, vær og eventuelle bønnetider;
velg «alt» når også felles fotball-, kalender- og nyhetsdata skal fornyes.
Menyen bruker `external-data:refresh` etterfulgt av `external-data:warm`, uten å
tømme cache. Les resultatet: kommandoen beholder reservecache der dette støttes,
men returnerer feilstatus også ved delvis feil.

Menyen installeres på en ny Windows-PC fra den versjonerte filen:

```powershell
New-Item -ItemType Directory -Force "$HOME\PowerShellScripts"
Copy-Item .\tools\windows\innsatt.ps1 "$HOME\PowerShellScripts\innsatt.ps1"
function innsatt { & "$HOME\PowerShellScripts\innsatt.ps1" }
```

`docker01` og `innsatt-prod` må finnes som personlige SSH-aliaser med egne
nøkler. Ikke lagre nøkler eller passord i repositoryet.

## Ved mislykket deploy eller tilbakeføring

1. Stopp etter den feilede deployen; ikke tøm cache eller start dataoppdatering
   for å skjule feilen.
2. Ta vare på deployutskriften og bruk menyvalg **8** for å undersøke serveren.
   Prosjektet ligger dokumentert som `/home/forge/innsatt.no/innnsattno`.
3. Kontroller de tre inngangssidene når tjenesten igjen svarer. Hvis siste
   godkjente versjon må gjenopprettes, bruk serverens vanlige deploy-/release-
  mekanisme med sist kjente gode commit og dokumenter utfallet her.

Det finnes ikke en versjonert, verifisert manuell rollback-kommando i dette
repositoryet. Ikke gjett en slik kommando; avklar den med serveransvarlig før
tilbakeføring.

## Legge til et nytt fengsel

1. Kopier den kommenterte malen i `config/prisons.example.php` inn i
   `config/prisons.php` under `all`. Bruk en unik URL-ID, verifisert navn og
   bare dokumenterte lokale data.
2. Sett alle funksjonsbrytere. Deaktiverte tjenester skal stå `false` og skal
   ikke få kanaler, nyhetskilder eller visitasjonsdata fra et annet fengsel.
3. Legg bare inn verifiserte værkoordinater, TV-kanaler fra VGs TV-guide,
   bønnetids-ID, offentlige nyhetskilder og eventuelle visitasjonsceller.
   Nye værmiljøvariabler følger mønsteret `WEATHER_<ID>_LATITUDE/LONGITUDE`.
4. Kjør `php artisan prisons:validate`, målrettede tester og test det nye
   `/{fengsel}`-prefikset lokalt. Kommandoen gjør ingen nettverkskall eller
   dataendringer.
5. Commit og push, og gjennomfør vanlig manuell deploy. Oppdater PowerShell-
   menyen dersom den skal tilby et eget valg for målrettet dataoppdatering av
   fengselet; dette er det eneste manuelle tilleggssteget utenfor registeret.

Ruter, kontekstnavigasjon og planlagte kontroller leser aktive fengsler fra
registeret. Felles sider og visninger gjenbrukes; et nytt fengsel skal ikke ha
egne sidekopier.

## Mer dokumentasjon

- `PROJECT.md` og `context/project-rules.md`: arbeidsflyt, testing og Git.
- `README.md`: utviklings- og datakildedokumentasjon.
- `docs/`: avgrenset fag- og tjenestedokumentasjon.
- Resten av denne filen: bekreftet historikk for manuelle serverendringer.

## Historikk – manuelle produksjonsendringer

## 2026-10-24 – Servervedlikehold og oppdatert produksjonsstatus

### Produksjonsmiljø

Produksjonsserveren er en DigitalOcean VPS med Ubuntu 24.04. Prosjektet ligger i
`/home/forge/innsatt.no/innnsattno`.

| Komponent | Versjon / status |
| --- | --- |
| Linux-kjerne | 6.8.0-147-generic |
| Nginx | 1.28.0 |
| PHP | 8.3.36 |
| MySQL | 8.0.46 |
| Redis | 8.10.2 |
| Node.js | 22.23.4 |
| Laravel | 9.52.20 |
| cloud-init | 26.2 |

Laravel kjører med produksjonsmiljø og debug deaktivert. Laravel Scheduler
startes hvert minutt fra `forge`-brukerens crontab:

```cron
* * * * * cd /home/forge/innsatt.no/innnsattno && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

### Utført vedlikehold

Ubuntu-pakker, PHP, MySQL, Redis, Node.js, cloud-init og Linux-kjernen ble
oppdatert 24.10.2026. Serveren ble deretter startet på nytt.

### Kontroller etter omstart

- Den nye Linux-kjernen var aktiv.
- SSH og nettverk fungerte.
- Nginx, PHP-FPM, MySQL og Redis var aktive.
- innsatt.no svarte normalt.
- Laravel startet uten feil.
- Laravel Scheduler var registrert og ble startet fra cron etter omstart.
- Det ble ikke funnet feilede systemd-tjenester eller uferdige pakkeinstallasjoner.

### Kjente oppfølgingspunkter

- Laravel 9 må oppgraderes til en støttet versjon.
- `nginx` og `nginx-common` er midlertidig satt på hold på grunn av forskjeller
  mellom pakkekilder. En trygg oppgraderingsstrategi må vurderes før låsene
  eventuelt fjernes.
- `libgd3` er midlertidig satt på hold for å beholde Ubuntu-versjonen fremfor
  Sury-versjonen. Vurder APT-pinning slik at sikkerhetsoppdateringer fortsatt
  kan mottas.
- Ikke fjern disse pakkelåsene automatisk.

## 2026-10-04 – Poppler for Fangenytt-forsider

### Produksjonsserver

- `poppler-utils` ble installert via `apt`.
- Produksjonen har nå `/usr/bin/pdftoppm`, versjon 24.02.0.
- Verktøyet genererer komprimerte JPEG-forsider fra første side i lokale Fangenytt-PDF-er.

Deretter ble følgende kjørt uten feil:

```bash
sudo -u forge php artisan fangenytt:covers
```

Forsider for Fangenytt nr. 1–18 ble generert i `storage/app/fangenytt/covers/`.

### Merknader

- PDF- og coverfilene ligger utenfor Git.

## 2026-09-22 – Avgrenset blokkering av distribuert bot på «Dagen i dag»

### Bakgrunn

- En distribuert bot ga 617 treff på daterte «Dagen i dag»-sider med samme User-Agent fra mange IP-adresser.

### Produksjonsserver

- Fil: `/etc/nginx/sites-available/innsatt.no`
- Det er lagt inn en regel som svarer med HTTP 403 bare når den aktuelle User-Agent-en besøker en datert URL på formen `/dagen-i-dag/YYYY-MM-DD`.
- Andre URL-er og andre User-Agents omfattes ikke av regelen.

### Verifisering

- `nginx -t`: OK
- Nginx ble lastet inn på nytt etter valideringen.

## 2026-09-16 – Automatisk nyhetstjeneste på forsiden

Den automatiske forsidetjenesten for kriminalomsorgsnyheter ble deployet med hovedcommit `d38189983f7c508b7b59ab36cb13b5dd7574f13e` og etterfølgende justering `8b6e260f795d44bd0900fe6914dc344075487b8c`.

### Produksjonsserver

- Migreringene `2026_09_16_000000_create_correctional_news_clusters_table`, `2026_09_16_000100_create_correctional_news_items_table` og `2026_09_16_000200_add_national_significance_to_correctional_news_tables` ble kjørt uten feil.
- Første produksjonsinnhenting lyktes for NFF-magasinet, KY, KDI/NTB og Sivilombudet.
- Forsiden svarte HTTP 200.
- NFF- og KY-saker vises bare under «Fra fagforeningene».
- «Nasjonalt» skjules når ingen KDI- eller Sivilombudet-saker kvalifiserer.

Laravel-scheduleren ble aktivert i `forge`-brukerens crontab:

```cron
* * * * * cd /home/forge/innsatt.no/innnsattno && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

Cron ble bekreftet kjørt hvert minutt gjennom systemloggen. Laravel kjører KDI hvert 15. minutt og NFF, KY og Sivilombudet hvert 30. minutt. Scheduleroppgavene bruker `withoutOverlapping()`.

### Senere kontroll

```bash
sudo -u forge crontab -l
sudo -u forge /usr/bin/php /home/forge/innsatt.no/innnsattno/artisan schedule:list
sudo -u forge /usr/bin/php /home/forge/innsatt.no/innnsattno/artisan correctional-news:fetch
```

## 2026-09-10 – Blokkering av SERankingBacklinksBot

### Produksjonsserver

- Fil: `/etc/nginx/sites-enabled/innsatt.no`

Lagt inn regel:

```nginx
if ($http_user_agent ~* "SERankingBacklinksBot") {
    return 403;
}
```

### Bakgrunn

- SERankingBacklinksBot sto for 593 requests i de gjennomgåtte Nginx-loggene.
- 68 av disse gikk mot bønnetidssidene.
- Bot-en traff systematisk blant annet parameteriserte bønnetids-URL-er og utskriftssider.
- En request mot `/bonnetider?year=2027&month=1` sammenfalt svært tett med et Bonnetid.no-feilvarsel og var sannsynlig årsak til dette.
- Bot-en ble derfor blokkert i Nginx før Laravel.

### Verifisering

- `nginx -t`: OK
- `systemctl reload nginx`: utført
- Vanlig request mot `https://innsatt.no/bonnetider`: HTTP 200
- Request med `SERankingBacklinksBot` som User-Agent: HTTP 403

### Merknader

- Ingen Laravel-kode ble endret.
- Endringen er gjort direkte på produksjonsserveren.
- Regelen står foreløpig direkte i `/etc/nginx/sites-enabled/innsatt.no`.
- Den kan senere flyttes til en Forge include-fil dersom vi ønsker en mer varig løsning.
