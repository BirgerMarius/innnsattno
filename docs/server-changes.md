# Manuelle produksjonsendringer

Denne filen er en løpende changelog og huskeliste for endringer som gjøres direkte på produksjonsserveren og derfor ikke automatisk ligger i Git-repositoryet. Dokumenter blant annet Nginx-, systemd-, cron-/timer-, firewall- og annen manuell serverkonfigurasjon her.

## 2026-09-10 – Blokkering av SERankingBacklinksBot

### Produksjonsserver

- Fil: `/etc/nginx/sites-enabled/innsatt.no`

Lagt inn regel:

```nginx
if ($http_user_agent ~* "SERankingBacklinksBot") {
    return 403;
}
```

### Bakgrunn

- SERankingBacklinksBot sto for 593 requests i de gjennomgåtte Nginx-loggene.
- 68 av disse gikk mot bønnetidssidene.
- Bot-en traff systematisk blant annet parameteriserte bønnetids-URL-er og utskriftssider.
- En request mot `/bonnetider?year=2027&month=1` sammenfalt svært tett med et Bonnetid.no-feilvarsel og var sannsynlig årsak til dette.
- Bot-en ble derfor blokkert i Nginx før Laravel.

### Verifisering

- `nginx -t`: OK
- `systemctl reload nginx`: utført
- Vanlig request mot `https://innsatt.no/bonnetider`: HTTP 200
- Request med `SERankingBacklinksBot` som User-Agent: HTTP 403

### Merknader

- Ingen Laravel-kode ble endret.
- Endringen er gjort direkte på produksjonsserveren.
- Regelen står foreløpig direkte i `/etc/nginx/sites-enabled/innsatt.no`.
- Den kan senere flyttes til en Forge include-fil dersom vi ønsker en mer varig løsning.
