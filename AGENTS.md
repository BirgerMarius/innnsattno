# Fengselskontekst

`/ringerike`, `/ilseng` og `/kongsvinger` er inngangene til Innsatt.no. Ikke
bruk cookie, sesjon eller referer for å velge fengsel. Nye lokale funksjoner
skal bruke `config/prisons.php` og URL-prefikset `/{fengsel}/...`.

Gjenbruk eksisterende kontrollere og Blade-visninger. Ikke kopier en side per
fengsel. Når en ny lenke, utskrift eller cache legges til, må fengselssluggen
inngå i URL-en og cache-/operasjonsnøkkelen. Kongsvinger har ikke mottatt
kanalliste; det er aldri tillatt å falle tilbake til et annet fengsels TV-kanaler.
