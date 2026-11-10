# Automatiske dataoppdateringer i admin

Adminoversikten på `/adm` leser bare lagrede rader i `external_data_checks`; den starter aldri planlagte jobber, kildehentinger eller nettverkskall. TV-programavvik er fortsatt i den eksisterende, detaljerte TV-seksjonen og gjentas ikke i tabellen.

«Siste kildeforsøk» er tidspunktet for den sist registrerte kontrollen. «Siste vellykkede kildehenting» oppdateres bare når den eksterne kilden svarte med et gyldig, brukbart resultat. Dette bekrefter ikke at innholdet er endret siden forrige henting.

Statusen «Brukbare reservedata» betyr at siste kildeforsøk feilet, men at validert cache eller en tillatt reserve fortsatt dekker dataene. «Data mangler» betyr at minst én kontroll ikke har brukbare data. «Ikke registrert» er ukjent historikk, ikke en feil. Deaktiverte funksjoner fra `config/prisons.php` vises som «Deaktivert».

Rytmen leses fra Laravel-schedulerens plan for `external-data:check-others`; den viser når scheduler kontrollerer om en kilde er klar for henting. Den er ikke et løfte om at innholdet endrer seg ved hver kjøring.
