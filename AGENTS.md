# Fengselskontekst

`/ringerike`, `/ilseng` og `/kongsvinger` er inngangene til Innsatt.no. Ikke
bruk cookie, sesjon eller referer for å velge fengsel. Nye lokale funksjoner
skal bruke `config/prisons.php` og URL-prefikset `/{fengsel}/...`.

Gjenbruk eksisterende kontrollere og Blade-visninger. Ikke kopier en side per
fengsel. Når en ny lenke, utskrift eller cache legges til, må fengselssluggen
inngå i URL-en og cache-/operasjonsnøkkelen. Kongsvinger har ikke mottatt
kanalliste; det er aldri tillatt å falle tilbake til et annet fengsels TV-kanaler.

TV-kanaler ligger i `config/prisons.php` som `tv_channels`. «Fotball på TV»
leser bare lokal TV-guide-cache ved sidevisning. Klassifiser hele kamper fra
verifiserte VG `sportsTitle`-ID-er/slugs og dokumenterte tittelaliaser; hold
studio, magasin, oppsummering og høydepunkter utenfor. Bruk TV-guidens
`startsAt` i Europe/Oslo som sendestart. `isRerun` betyr «Reprise», deretter
betyr `isLive` «Direkte», og ellers er sendetypen ukjent. Avdupliser konkret
VG-listing (`listing.id`, med kanal + sendestart + programidentitet som fallback),
aldri bare kamp-ID: flere sendinger av samme kamp skal beholdes. Kalenderuken
inkluderer dessuten natt til påfølgende mandag fram til 06.00, og både side og
utskrift skal bruke samme lokale datasett.

Lokale nyheter bruker én felles `LocalNewsService`. Kilden ligger i
`config/prisons.php` under hvert fengsels `local_news` med avisnavn og én eller
flere `sources` med emneside og eksplisitt tillatte artikkelverter. Behold bare
offentlig teaserdata, avdupliser flere innganger på normalisert artikkellenke,
og bruk bare offentlig publiseringsdato ved sortering. Sørg for at cache, lås
og driftskontroll inneholder fengselssluggen. Et fengsel må aldri vise en annen
kildes reservecache.
