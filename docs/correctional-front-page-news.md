# Automatiske nyheter på forsiden

Denne funksjonen er uavhengig av den eksisterende `/nyheter`-tjenesten. Den brukes bare av forsiden og lagrer kun nødvendig metadata: tittel, kilde, direkte lenke, tidspunkt, relevans, observasjonstid og saksklynge. Artikkeltekst, bilder og fullstendige ingresser lagres eller vises ikke.

## Kilder og henting

`correctional-news:fetch` henter hver kilde separat. Kildene er konfigurert i `config/correctional_news.php`:

- NFF-magasinet: `https://www.frifagbevegelse.no/nff-magasinet/?lab_viewport=rss`
- Kriminalomsorgens Yrkesforbund: `https://kysiden.no/feed/`
- Kriminalomsorgsdirektoratet/NTB: `https://kommunikasjon.ntb.no/rss/releases/latest?publisherId=17847130`
- Sivilombudet: `https://www.sivilombudet.no/feed/`

KDI hentes hvert 15. minutt, de øvrige RSS-kildene hvert 30. minutt. Ringblad hentes fortsatt av den eksisterende `RingbladNewsService` med dens uendrede tre-timers cache, URL-kontroll og stale-fallback.

Eksempler:

```sh
php artisan correctional-news:fetch
php artisan correctional-news:fetch --source=kdi
```

RSS leses lokalt med XML-nettverk deaktivert. Timeout, HTTP-feil, tom/ugyldig XML og parserfeil isoleres per kilde. Tidligere lagrede gyldige klynger beholdes til normal utløpsdato, og `ExternalDataFailureNotifier` bruker sin vanlige cooldown for varsling.

## Utvalg og gruppering

Nasjonale artikler må komme fra KDI eller Sivilombudet og bestå både relevansgrensen og en separat, lokal score for nasjonal betydning. Sistnevnte krever eksplisitte signaler i overskrift eller feedmetadata, som budsjett/sparetiltak, kapasitet, lov/forskrift, landsdekkende bemanning eller systemiske tilsynsfunn. Rutinemessige nøkkeltall kvalifiserer ikke bare fordi de kommer fra KDI. NFF- og KY-saker er alltid fagforeningsstoff og vises aldri i den nasjonale gruppen, uansett score. URL-er og feedetiketter for `debatt`, `meninger`, `kommentar` og `kronikk` blir aldri nasjonale, men kan fortsatt vises under fagforeningene.

URL-sporing fjernes før lagring. Artikler samles i én saksklynge når de ligger i et 72-timers vindu, har minst 0,60 tittellikhet (0,65 for fagforeningsklynger) og deler et særpreget tema/entitet eller to felles signaler. Generiske ord som «fengsel» og «Kriminalomsorgen» er ikke nok alene. Den offentlige og åpne kilden prioriteres, deretter relevans, faglig kilde og tidlig publisering.

Forsiden viser maksimalt tre lokale Ringblad-saker og tre nasjonale klynger. Nasjonale klynger utløper etter ti dager. Fagforeningslisten viser maksimalt fem unike NFF/KY-klynger i inntil 30 dager og reserverer en plass til hver når begge har ferske saker.

## Drift før produksjonssetting

Migrering og scheduler er ikke aktivert på noen server av denne endringen. Ved senere godkjent utrulling må den ansvarlige operatøren kjøre migrering i riktig miljø og sørge for at Laravel-scheduleren kjøres hvert minutt, for eksempel:

```cron
* * * * * cd /path/to/innsatt.no && php artisan schedule:run >> /dev/null 2>&1
```

Alternativt kan en systemd-timer eller plattformens planlagte jobb kjøre samme kommando hvert minutt. Dette må tilpasses faktisk PHP-binær, bruker, prosjektsti og loggrutine på serveren; det er ikke gjort av denne endringen.
