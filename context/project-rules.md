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
