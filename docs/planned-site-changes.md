# Planlagte, synlige endringer

«Automatikk → Planlagte endringer» i admin viser bare hendelser som faktisk kan
endre noe synlig på nettstedet. Tidspunkt vises alltid i `Europe/Oslo` og passerte
hendelser filtreres bort når siden lastes.

Tidsvinduer som vises og skjules på et bestemt tidspunkt skal bruke
`TimedVisibilityService` både i funksjonen som styrer visningen og i
`PlannedSiteChangesService::windowChanges()`. Da er varighet og starttid ett
datagrunnlag, ikke to parallelle datoer. For regelbaserte funksjoner (som
klokkeomstillingen) skal kildetjenesten selv tilby kommende hendelser, slik
`ClockChangeReminderService::upcomingChanges()` gjør.

Når en ny tidsstyrt, offentlig funksjon legges til, registrer dens visnings- og
skjulehendelse i `PlannedSiteChangesService`, og legg til test for både funksjonen
og planlisten. Ikke legg inn datoer i admin-visningen.
