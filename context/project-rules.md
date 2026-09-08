# Prosjektregler for innsatt.no

Prosjektnavn: innsatt.no

Rammeverk: Laravel

## Praktisk arbeidsflyt

- Brukeren og ChatGPT avklarer ønsket resultat og viktige begrensninger.
- Codex får normalt én samlet oppgave om å undersøke, implementere, teste og rapportere.
- Work packages er valgfrie og brukes bare for store, risikofylte eller langvarige oppgaver.
- Codex skal ikke committe, pushe eller deploye uten uttrykkelig beskjed.
- Produksjonsdeploy er alltid et separat steg.

## Codex kan gjøre uten ny tillatelse

- Lese og søke i repositoryet.
- Endre nødvendige filer innenfor avtalt oppgave.
- Kjøre målrettede tester og hele testpakken.
- Kjøre linting, bygging, kompilering og formatteringskontroller.
- Kjøre `git diff --check`, `git status` og andre lesende Git-kommandoer.
- Tømme lokale cacher.
- Starte eller restarte lokale utviklingstjenester og containere når det er nødvendig for testing.
- Opprette midlertidige testdata som ikke påvirker produksjon eller ekte data.

## Git

- Alle Git-commitmeldinger skal være på norsk.
- Commitmeldinger skal være korte, tydelige og beskrive den faktiske endringen.
- Commit og push utføres bare etter uttrykkelig beskjed.
- En commit skal bare inneholde filer som tilhører den avtalte oppgaven.
- Urelaterte lokale endringer skal bevares og rapporteres.

## Begrensninger

- Bevar eksisterende arkitektur.
- Bruk felles layout i `resources/views/layouts/app.blade.php`.
- Bruk felles header i `resources/views/partials/header.blade.php`.
- Bruk felles footer i `resources/views/partials/footer.blade.php`.
- Bruk global egendefinert CSS i `public/css/custom/app.css`.
- Gjør minste nødvendige endring.
- Fullfør én logisk oppgave om gangen.
- Ikke utfør produksjonsdeploy uten uttrykkelig beskjed om deploy eller produksjon.
- Ikke endre produksjonsdata, hemmeligheter, større avhengigheter eller urelaterte filer uten særskilt godkjenning.

## Utskriftssider

Nye print-/utskriftssider skal som hovedregel følge det etablerte mønsteret fra TV-guide og bønnetider:

- Bruk en egen utskriftsvisning/-rute når det gir et bedre print-layout.
- Åpne print-dialogen automatisk når utskriftssiden lastes.
- Lytt på nettleserens `afterprint`-event. Det kjøres både når brukeren skriver ut og når dialogen avbrytes.
- Beskytt tilbakegangen med en engangsvakt, og bruk `window.location.replace(...)` eller samme etablerte mekanisme når dialogen lukkes.
- Retur-URL-en skal bevare relevant kontekst, som måned, år, lag, fengsel eller andre parametere. Ikke fastkod én returside når utskriftsvisningen kan åpnes fra flere steder.
- Hold print-layout og navigeringslogikk adskilt.

Se `resources/views/pdf.blade.php`, `resources/views/prayer/print.blade.php` og `PrayerController` som eksisterende eksempler.

## Eksterne datakilder

Controlleren håndterer request og view; et service-lag håndterer ekstern integrasjon og forretningslogikk. Nye server-side integrasjoner mot API-er eller nettsider skal derfor normalt ikke være rå HTTP-kall direkte i ruter eller kontrollere.

- Bruk Laravels `Http`-klient med eksplisitt `connectTimeout` og total timeout.
- Retry skal være begrenset og bare brukes for forbigående connection-/timeout-/5xx-feil når det er hensiktsmessig. Ikke retry blindt på 4xx.
- Kontroller HTTP-status, valider JSON/payload/schema og håndter exceptions kontrollert. En ekstern feil skal så langt mulig ikke gi HTTP 500 på resten av siden.
- Bruk fresh cache for data som egner seg for caching, og last-known-good/stale fallback når det er hensiktsmessig. Cache-identiteten må inneholde alle dataavgjørende parametere, for eksempel dato, lokasjon, sesong eller kanalutvalg; fallback må aldri bruke feil dato, lokasjon eller periode.
- Isoler feil fra separate datakilder/endepunkter, slik at én svikt ikke unødvendig feller resten av siden.

Cache beskytter mot kortvarige eksterne feil og unødvendige API-kall. Tester skal dekke både normal respons og relevant fallback. Se blant annet `TvGuideService`, `PrayerTimeService`, `FootballWorldCupService`, `RingbladNewsService`, `WeatherForecastService`, `SchibstedCompetitionService`, `NamedayService` og `OnThisDayService`.

Vurder Laravels rate limiting for dynamiske eller API-tunge sider som kan bli utsatt for aggressiv crawling. Bruk `robots.txt` til å begrense unødvendig crawlertrafikk. Rate limiting er intern trafikkbeskyttelse og skal ikke trigge `ExternalDataFailureNotifier`.

### Varsling av eksterne feil

Bruk `app/Services/ExternalDataFailureNotifier.php` som standardmekanisme for relevante produksjonsintegrasjoner. Kallet skal ha stabil `service` og `operation`, en kort sanitert `summary`, eventuelt `failure_kind`, og HTTP-status når det er relevant.

- Ikke send API-nøkler, tokens, Authorization-headere, cookies, passord/secrets, request body, rå sensitive URL-/query-parametere eller full rå payload til notifieren eller e-post.
- Bruk eksisterende cooldown/deduplisering; ikke lag en egen e-postmekanisme per integrasjon, ikke spam samme feil og unngå dobbeltlogging når notifieren allerede logger.
- Notifier- eller mail-feil må aldri påvirke nettsiden.
- Koble ikke notifieren blindt på alt: automatiske/systemkritiske produksjonsintegrasjoner varsles normalt; brukerutløste mindre kritiske tjenester vurderes ut fra signal/støy. Normale tomme resultater, bruker-/valideringsfeil og rene CLI-/diagnoseverktøy skal normalt ikke varsles som ekstern driftsfeil.

Notifieren er felles driftsovervåking, mens tjenesten fortsatt har ansvar for kontrollert feil- og fallbackhåndtering.
