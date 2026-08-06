# Prosjekt

Versjon: 2.0

---

# Prosjektinformasjon

## Navn

innsatt.no

## Repository

git@github.com:BirgerMarius/innnsattno.git

## Teknologi

- Laravel
- PHP 8
- Livewire
- Blade
- MySQL-kompatibel Laravel-databasekonfigurasjon
- Laravel Mix

---

# Formål

innsatt.no er en Laravel-nettside. AI Factory skal støtte enkel, trygg og praktisk videreutvikling uten unødvendige prosesstrinn.

---

# Normal arbeidsflyt

1. Brukeren og ChatGPT avklarer ønsket resultat, viktige begrensninger og hva som ikke skal endres.
2. Codex får normalt én samlet oppgave om å undersøke løsningen, implementere endringen, oppdatere tester, kjøre nødvendige kontroller og rapportere resultatet.
3. Codex skal ikke committe, pushe eller deploye i første fase med mindre dette er uttrykkelig bestilt.
4. Etter godkjenning får Codex en kort beskjed om å committe og pushe kun relevante filer.
5. Produksjonsdeploy utføres separat og bare etter uttrykkelig beskjed.

Work packages er valgfrie og brukes bare for store, risikofylte eller langvarige oppgaver som trenger et varig stoppunkt.

---

# Omfang

Inkludert:

- Løpende vedlikehold og videreutvikling av Laravel-applikasjonen.
- Endringer i Blade, CSS, ruter, kontrollere, tjenester, tester og dokumentasjon når dette er nødvendig for den avtalte oppgaven.
- Små og avgrensede forbedringer direkte i eksisterende arkitektur.

Utenfor omfang uten særskilt godkjenning:

- Produksjonsdeploy.
- Destruktive databaseoperasjoner eller endring av produksjonsdata.
- Større omskrivinger, rammeverksmigreringer eller vesentlige endringer i avhengigheter.
- Urelatert opprydding eller refaktorering.
- Reversering eller inkludering av urelaterte lokale endringer.

---

# Kodestandard

Bevar eksisterende arkitektur og stil. Gjør minste nødvendige endring og fullfør én logisk oppgave om gangen.

---

# Testing og lokale tjenester

Codex har generell tillatelse til å:

- lese og søke i repositoryet
- kjøre målrettede tester og hele testpakken
- kjøre linting, bygging, kompilering og formatteringskontroller
- kjøre `git diff --check`, `git status` og andre lesende Git-kommandoer
- tømme lokale cacher
- starte eller restarte lokale utviklingstjenester og containere når dette er nødvendig for testing
- opprette midlertidige testdata som ikke påvirker produksjon eller ekte data

Codex skal velge relevante tester ut fra endringen og rapportere tydelig hva som er kjørt og resultatet.

---

# Git

- Commit og push er separate fra implementering og utføres bare når dette er uttrykkelig bestilt.
- Alle Git-commitmeldinger skal være på norsk.
- Commitmeldingen skal være kort, tydelig og beskrive den faktiske endringen.
- Committen skal bare inneholde filer som tilhører den avtalte oppgaven.
- Urelaterte lokale endringer skal bevares og rapporteres.

---

# Deploy

Ingen produksjonsdeploy skal utføres uten uttrykkelig beskjed om deploy eller produksjon. Commit og push innebærer aldri automatisk deploy.

---

# AI-instruksjoner

- Bevar eksisterende arkitektur.
- Bruk `resources/views/layouts/app.blade.php` for felles layoutarbeid.
- Bruk delte header- og footer-partials når det er relevant.
- Bruk `public/css/custom/app.css` for global egendefinert CSS.
- Gjør minste nødvendige endring.
- Fullfør én logisk oppgave om gangen.
- Kjør nødvendige tester og lokale restarter uten å be om ny tillatelse for hver kommando.
- Ikke commit, push eller deploy uten uttrykkelig beskjed.
- Skriv alltid Git-commitmeldinger på norsk.
- Ikke endre produksjonsdata, hemmeligheter eller urelaterte filer uten særskilt godkjenning.

---

# Referanser

- `project.yaml`
- `context/project-rules.md`
- `README.md`
