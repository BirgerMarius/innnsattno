# Tredjepartsressurser i nettleseren

## Lokale statiske biblioteker

Bootstrap 5.3.7 leveres lokalt fra `public/vendor/bootstrap/5.3.7/`:

- `css/bootstrap.min.css`
- `js/bootstrap.bundle.min.js`
- `LICENSE` (MIT)

Filene er uendrede distribusjonsfiler hentet fra Bootstrap 5.3.7 via jsDelivr,
med lisensen fra Bootstrap sitt offisielle v5.3.7-tag. Versjonsnummeret ligger i
stien, slik at oppdatering er eksplisitt og cache-sikker. Ved en senere,
planlagt oppgradering skal den nye versjonen lastes ned fra Bootstrap sin
offisielle distribusjon, plasseres i en egen versjonsmappe med tilhørende
`LICENSE`, og begge referansene i `resources/views/layouts/app.blade.php`
oppdateres samtidig. Ikke oppdater biblioteket med en ny byggeprosess.

## Eksterne ressurser som beholdes

Microsoft Clarity er en bevisst valgt analysetjeneste og lastes fortsatt fra
Clarity. Font Awesome Pro 5.13.0 beholdes som ekstern ressurs: den brukes av
aktive ikoner, og lokal kopiering skal ikke gjøres før Pro-lisensen er avklart.

Datakilder og innholdsressurser, som flaggbilder fra FlagCDN, er ikke statiske
biblioteker og omfattes ikke av denne lokale leveringen. Bootstrap CSS bruker
kun innebygde SVG-dataadresser og refererer ikke til separate fonter eller
bildefiler.
