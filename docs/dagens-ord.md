# Dagens ord

Tekstene ligger lokalt i `config/daily_word.php`. De skal være varme, jordnære
og passende både for innsatte og ansatte. Unngå bastante råd, bagatellisering
av vanskelige situasjoner og sitater med usikker kilde.

`DailyWordService` bruker kalenderdatoen i `Europe/Oslo` og en enkel,
gjentakende rotasjon fra `anchor_date`. Derfor får alle besøkende samme tekst
hele dagen, også etter oppdatering av siden. Med dagens 61 tekster kommer samme
tekst tilbake etter 61 dager, og aldri på to dager etter hverandre.

Det er trygt å rette ordlyden i en tekst, og hver tekst har en stabil `id` som
brukes av utskriftslenken. Ikke gjenbruk eller endre en ID etter publisering;
da kan en lenke åpnet like før midnatt fortsatt skrive ut samme valgte tekst.
Vær oppmerksom på at endringer i rekkefølgen eller antallet tekster endrer
hvilken tekst enkelte datoer får. Dette er forventet for den enkle rotasjonen
og krever ingen lagret plan. Test gjerne datoen du er opptatt av med
`DailyWordServiceTest` etter redigering.

Klokkestillingsmeldingen har høyere prioritet enn Dagens ord. Når den ikke
lenger er aktiv, velges Dagens ord automatisk igjen.

Hvert Dagens ord er en vanlig tastaturtilgjengelig lenke til en stående
A4-utskrift. Den automatiske utskrifts- og returfunkjsonen er den samme som
for nettstedets øvrige utskrifter. Selve utskriften inneholder bare mottoet og
den lille avsenderen «innsatt.no».
