# Rutine for servervedlikehold

Denne rutinen gjelder operativsystemet og pakkene på produksjonsserveren. Den
erstatter ikke [vanlig applikasjonsdeploy](server-changes.md#vanlig-deploy), og
applikasjonsdeploy erstatter ikke denne rutinen.

## Frekvens og prioritering

- Ukentlig: kontroller sikkerhetsvarsler, tilgjengelige oppdateringer, feilede
  tjenester, diskplass og behov for omstart.
- Månedlig: gjennomfør planlagt vedlikehold med manuell installasjon og
  etterkontroll.
- Kritiske sikkerhetsoppdateringer: vurder dem straks, uten å vente på neste
  planlagte vedlikeholdsvindu.

Automatisk oppgradering, automatisk omstart, `autoremove`, pakkekildebytte og
fjerning av pakkelåser skal ikke legges inn som del av denne rutinen. Slike
endringer må vurderes og gjennomføres manuelt og kontrollert.

## Før vedlikehold

1. Registrer tidspunkt, vedlikeholdsomfang, nåværende versjoner og relevante
   sikkerhetsvarsler.
2. Kontroller aktive APT-kilder, prioriteringer/pinning og pakkelåser. Vurder
   konsekvensene av tredjepartskilder og behold eksplisitte låser til de er
   vurdert.
3. Kontroller feilede tjenester, diskplass og om serveren krever omstart før
   arbeidet starter.
4. Ta en tidsstemplet sikkerhetskopi av berørt konfigurasjon, inkludert ved
   behov `/etc/nginx`, `/etc/php` og `/etc/apt`.
5. Oppdater pakkelister og simuler de aktuelle oppdateringene. Gjennomgå hvilke
   pakker som skal installeres, oppgraderes eller fjernes, og stopp dersom
   simuleringen viser uventede endringer.

Et konfigurasjonsarkiv kan gjenopprette konfigurasjon, men tilbakefører ikke
pakker. Rollback krever tilgjengelige tidligere pakker eller en verifisert
serverbackup eller snapshot.

## Kontrollert gjennomføring

1. Installer bare de vurderte oppdateringene manuelt.
2. Ved pakkekildebytte, fjerning av pakker eller endring av en pakkelås skal
   årsak, forventet pakkeutfall og tilbakeføringsmulighet dokumenteres før
   endringen.
3. Test konfigurasjonen før berørte tjenester restartes, for eksempel NGINX og
   PHP-FPM.
4. Restart bare tjenestene som må restartes, og bekreft at de er aktive etterpå.

Servervedlikehold skal ikke omfatte Laravel-deploy, migreringer,
applikasjonsoptimalisering eller cachetømming. Eksterne datacacher skal særlig
ikke tømmes som del av vedlikeholdsrutinen; de bevarer siste brukbare data ved
forbigående kildefeil.

## Etterkontroll

1. Kontroller pakkestatus og versjoner, samt at APT ikke har uferdige eller
   ødelagte avhengigheter.
2. Kontroller at berørte tjenester er aktive og at konfigurasjonstestene besto.
3. Kontroller at alle tre inngangsider svarer normalt:
   `https://innsatt.no/ringerike`, `https://innsatt.no/ilseng` og
   `https://innsatt.no/kongsvinger`.
4. Kontroller relevante funksjoner for de oppdaterte tjenestene, for eksempel
   TV-siden når NGINX eller PHP-FPM er berørt.
5. Vurder på nytt behovet for serveromstart, og noter eventuelle åpne avvik.

## Dokumentasjon og åpne oppfølginger

Før inn en historikkoppføring i [driftsveiledningen](server-changes.md) med
tidspunkt, berørte versjoner og pakker, konfigurasjonsendringer,
sikkerhetskopier, tester, tjenestestatus, HTTP-kontroller og åpne punkter.

Følgende oppfølginger er aktive:

- Vurder `libgd3`-låsen og eventuell APT-pinning.
- Kontroller faktisk dekning for automatiske sikkerhetsoppdateringer, inkludert
  tredjepartskilder.
- Oppgrader Laravel 9 til en støttet versjon.

## Automatisk kontroll av tilgjengelige oppdateringer

Kontrollen skiller privilegert APT-innhenting fra Laravel. Det root-eide
`scripts/server-maintenance-collect.sh` kjører `apt-get update` med tidsgrense,
simulerer oppgradering og publiserer bare et avgrenset JSON-resultat. Skriptet
installerer, fjerner eller endrer aldri pakker, kilder, låser eller omstart.

Laravel leser JSON via `server-maintenance:process-status`; kommandoen og HTTP
gjør ingen APT-kall. Siste gode pakkeliste beholdes ved innhentings- eller
JSON-feil, så feil vises aldri som «ingen oppdateringer». Statusen klassifiserer
heller ikke pakker som sikkerhetsoppdateringer uten separat kildegrunnlag.

### Manuell installasjon på produksjonsserveren

Stegene gjennomføres av serveransvarlig etter godkjent applikasjonsdeploy og
migrering; repositoryet aktiverer ikke jobben.

1. Fra `/home/forge/innsatt.no/innnsattno`, kjør som root:

   ```bash
   install -o root -g root -m 0750 scripts/server-maintenance-collect.sh /usr/local/sbin/innsatt-server-maintenance-collect
   install -o root -g root -m 0644 deploy/systemd/innsatt-server-maintenance.service /etc/systemd/system/innsatt-server-maintenance.service
   install -o root -g root -m 0644 deploy/systemd/innsatt-server-maintenance.timer /etc/systemd/system/innsatt-server-maintenance.timer
   install -d -o root -g forge -m 0750 /var/lib/innsatt-server-maintenance
   ```

2. Status- og feilstatusfilen opprettes atomisk som `root:forge`, modus `0640`.
   Sett
   `SERVER_MAINTENANCE_STATUS_PATH=/var/lib/innsatt-server-maintenance/status.json`.
   Sett også `SERVER_MAINTENANCE_ERROR_PATH=/var/lib/innsatt-server-maintenance/status.error.json`.
   `forge` skal ikke få sudo.
3. Angi `SERVER_MAINTENANCE_EMAIL_RECIPIENT=<driftsmottaker>`. La
   `SERVER_MAINTENANCE_EMAIL_ENABLED=false` stå til status og mottaker er
   verifisert.
   De nøyaktige miljøvariablene er:

   ```dotenv
   SERVER_MAINTENANCE_STATUS_PATH=/var/lib/innsatt-server-maintenance/status.json
   SERVER_MAINTENANCE_ERROR_PATH=/var/lib/innsatt-server-maintenance/status.error.json
   SERVER_MAINTENANCE_EMAIL_ENABLED=false
   SERVER_MAINTENANCE_EMAIL_RECIPIENT=<driftsmottaker>
   SERVER_MAINTENANCE_STALE_AFTER_HOURS=30
   ```

   Kjør deretter, som `forge` fra prosjektet, `php artisan migrate --force`.
4. Kjør `systemctl daemon-reload` og
   `systemctl start innsatt-server-maintenance.service`. Kontroller med:

   ```bash
   systemctl status innsatt-server-maintenance.service --no-pager
   cat /var/lib/innsatt-server-maintenance/status.json
   sudo -u forge test -r /var/lib/innsatt-server-maintenance/status.json
   ```
5. Kjør `php artisan server-maintenance:process-status` som `forge`, kontroller
   `/adm`, og aktiver deretter mail og
   `systemctl enable --now innsatt-server-maintenance.timer`.

Root-jobben er planlagt 04:45 med inntil ti minutters tilfeldig forsinkelse.
Laravel behandler filen 05:10 Europe/Oslo. Begge bruker låsing og tidsgrenser.
Pakkelisten omfatter kandidater fra alle aktive APT-kilder, også tredjepart.

### Varsling, kontroll og deaktivering

Nye eller endrede pakkeversjoner gir høyst én ordinær e-post per døgn; uendret
ventende status gir høyst én påminnelse per sju dager. Kontrollfeil gir ett
deduplisert feilvarsel og én friskmelding etter en vellykket kontroll. Levering
lagres varig og feilet sending prøves igjen uten å markeres som levert. En
vellykket kontroll uten oppdateringer sender ingen e-post.

Kontroller `/adm`, Laravel-logg og tabellene `server_maintenance_statuses` og
`server_maintenance_mail_deliveries`. For å deaktivere root-jobben, kjør
`systemctl disable --now innsatt-server-maintenance.timer`; behold statusfil og
historikk for feilsøking. Sett `SERVER_MAINTENANCE_EMAIL_ENABLED=false` for å
stanse e-post. Ikke bruk `autoremove` eller endre pakkelåser ved deaktivering.
