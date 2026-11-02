# Manuelle produksjonsendringer

Denne filen er en løpende changelog og huskeliste for endringer som gjøres direkte på produksjonsserveren og derfor ikke automatisk ligger i Git-repositoryet. Dokumenter blant annet Nginx-, systemd-, cron-/timer-, firewall- og annen manuell serverkonfigurasjon her.

## Drift – målrettet oppdatering av eksterne data

Etter vanlig deploy kan data oppdateres som `forge` fra prosjektmappen, uten
`cache:clear` eller `optimize:clear`:

```bash
cd /home/forge/innsatt.no/innnsattno
sudo -u forge /usr/bin/php artisan external-data:refresh --all
sudo -u forge /usr/bin/php artisan external-data:refresh --prison=ringerike
sudo -u forge /usr/bin/php artisan external-data:refresh --prison=ilseng
sudo -u forge /usr/bin/php artisan external-data:refresh --prison=kongsvinger
sudo -u forge /usr/bin/php artisan external-data:warm --prison=kongsvinger
```

`--all` oppdaterer dagens TV-guide, lokale nyheter, vær og eventuelle
bønnetider for alle fengsler. Den henter deretter felles fotball- og
VM-data én gang, samt navnedager, «Dagen i dag», kriminalomsorgsnyheter og
aktive nyhetsfeeder. `--prison` oppdaterer bare dagens TV-guide, lokale
nyheter, vær og eventuelle bønnetider for angitt fengsel. Felles data blir
ikke hentet ved fengselsvalg; kjør `--all` når de også skal fornyes.
For å fylle TV-data for en hel fotballuke etterpå brukes
`external-data:warm --prison=<fengsel>`. Den henter fra åtte dager tilbake til
tretten dager frem og tømmer ikke cache.

Kommandoen skriver en rad for hver del som er oppdatert, hoppet over eller
feilet. Uavhengige kilder fortsetter etter en feil, men kommandoen returnerer
feilkode ved hel eller delvis feil. Tjenestenes egne låser, reservecacher og
driftskontroller beholdes.

PowerShell-menyen ligger på administratørens PC i
`$HOME\PowerShellScripts\innsatt.ps1` og startes med funksjonen `innsatt`.
Den bruker OpenSSH, VS Code og SSH-aliasene `docker01` og `innsatt-prod`.
Funksjonen i PowerShell-profilen er:

```powershell
function innsatt { & "$HOME\PowerShellScripts\innsatt.ps1" }
```

Menyvalg 9 åpner cache-undermenyen. Der kan operatøren oppdatere alle data eller
data for Ringerike, Ilseng eller Kongsvinger. Serverkommandoene over må være
deployet før cachevalgene brukes.

På en ny PC opprettes mappen og den versjonerte kopien installeres slik:

```powershell
New-Item -ItemType Directory -Force "$HOME\PowerShellScripts"
Copy-Item .\tools\windows\innsatt.ps1 "$HOME\PowerShellScripts\innsatt.ps1"
```

Legg deretter funksjonen over i PowerShell-profilen og konfigurer `docker01` og
`innsatt-prod` i brukerens SSH-konfigurasjon med egne nøkler. Ved oppdatering på
en eksisterende PC skal gjeldende skript sikkerhetskopieres før overskriving:

```powershell
Copy-Item "$HOME\PowerShellScripts\innsatt.ps1" "$HOME\PowerShellScripts\innsatt.ps1.bak-$(Get-Date -Format yyyyMMddHHmmss)"
Copy-Item .\tools\windows\innsatt.ps1 "$HOME\PowerShellScripts\innsatt.ps1"
```

Den versjonerte kopien ligger i `tools/windows/innsatt.ps1` og følger vanlig
deploy. Ikke lagre private nøkler eller passord i repositoryet.

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
