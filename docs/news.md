# Kuraterte nyheter

Nyheter hentes til en modereringskø og blir aldri offentlige før en administrator publiserer dem. Forsiden og hovedmenyen er ikke koblet til nyhetssiden.

## Kilder og hentemetoder

| Slug | Kilde | Land | Metode |
| --- | --- | --- | --- |
| `nff-magasinet` | NFF-magasinet / FriFagbevegelse | Norge | RSS fra den dedikerte NFF-magasinet-feeden `https://www.frifagbevegelse.no/nff-magasinet/?lab_viewport=rss`. |
| `faengselsforbundet` | Fængselsforbundet | Danmark | RSS fra WordPress-feeden `/feed/`. |
| `seko-kriminalvarden` | Seko, bransje Vård | Sverige | Kildespesifikk HTML-parser. Kontrollerte RSS-adresser ga HTML/404 og ingen stabil feed ble funnet. Artikler filtreres konservativt etter tydelig kriminalomsorgsrelevans i tittel, ingress eller URL. |
| `corrections1` | Corrections1 Original Content | Internasjonalt | Kildespesifikk HTML-parser. Den oppgitte `original-content-rss`-adressen returnerer vanlig HTML (`text/html`), ikke XML. |

HTML-parserne er med vilje avgrenset til sine kilder og forventede artikkelkort. Ved endret sidemal kan de returnere null treff; hentestatusen bør derfor kontrolleres. Ingen betalingsmur eller innlogging omgås, og bare metadata/ingress lagres.

Seko-filteret krever tydelige svenske uttrykk knyttet til kriminalomsorg, fengsel, varetekt eller innsatte. Generelle organisasjons- og arbeidsmiljøsaker blir ikke tatt inn uten denne koblingen. Den konservative filtreringen kan utelate relevante saker når tittel, ingress og URL mangler tydelige nøkkelord; slike saker må eventuelt legges inn manuelt.

## Bruk

Kjør migrasjoner og den idempotente seederen:

```bash
php artisan migrate
php artisan db:seed --class=NewsSourceSeeder
```

Hent alle aktive kilder eller én kilde:

```bash
php artisan news:fetch
php artisan news:fetch --source=corrections1
```

Gå til `/admin/nyheter` med eksisterende admininnlogging. Fanen «Nye» viser `pending`. Der kan visningstittel og egen ingress lagres, og artikkelen publiseres, skjules, arkiveres eller flyttes tilbake til Nye. Kilder administreres på `/admin/nyheter/kilder`, hvor de kan aktiveres/deaktiveres og hentes enkeltvis. Offentlig side er `/nyheter`.

## Legge til en kilde

1. Legg kilden idempotent i `NewsSourceSeeder`.
2. Bruk `rss`/`atom` og en verifisert feed når mulig.
3. Hvis det ikke finnes en stabil feed, lag en egen parser som implementerer `ParserInterface`, avgrens `supports()` til kildens slug og registrer parseren i `NewsFeedService`.
4. Legg til en liten lokal fixture og parser-/feilhåndteringstest. Testene skal aldri kontakte live-nettsteder.

Det er foreløpig ingen scheduler. Manuell henting skal verifiseres før eventuell automatisk kjøring innføres.
