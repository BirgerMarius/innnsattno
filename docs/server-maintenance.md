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
