# Manuelle produksjonsendringer

Denne filen er en løpende changelog og huskeliste for endringer som gjøres direkte på produksjonsserveren og derfor ikke automatisk ligger i Git-repositoryet. Dokumenter blant annet Nginx-, systemd-, cron-/timer-, firewall- og annen manuell serverkonfigurasjon her.

## 2026-09-22 – Avgrenset blokkering av distribuert bot på «Dagen i dag»

### Bakgrunn

- En distribuert bot ga 617 treff på daterte «Dagen i dag»-sider med samme User-Agent fra mange IP-adresser.

### Produksjonsserver

- Fil: `/etc/nginx/sites-available/innsatt.no`
- Det er lagt inn en regel som svarer med HTTP 403 bare når den aktuelle User-Agent-en besøker en datert URL på formen `/dagen-i-dag/YYYY-MM-DD`.
- Andre URL-er og andre User-Agents omfattes ikke av regelen.

### Verifisering

- `nginx -t`: OK
- Nginx ble lastet inn på nytt etter valideringen.

## 2026-09-16 – Automatisk nyhetstjeneste på forsiden

Den automatiske forsidetjenesten for kriminalomsorgsnyheter ble deployet med hovedcommit `d38189983f7c508b7b59ab36cb13b5dd7574f13e` og etterfølgende justering `8b6e260f795d44bd0900fe6914dc344075487b8c`.

### Produksjonsserver

- Migreringene `2026_09_16_000000_create_correctional_news_clusters_table`, `2026_09_16_000100_create_correctional_news_items_table` og `2026_09_16_000200_add_national_significance_to_correctional_news_tables` ble kjørt uten feil.
- Første produksjonsinnhenting lyktes for NFF-magasinet, KY, KDI/NTB og Sivilombudet.
- Forsiden svarte HTTP 200.
- NFF- og KY-saker vises bare under «Fra fagforeningene».
- «Nasjonalt» skjules når ingen KDI- eller Sivilombudet-saker kvalifiserer.

Laravel-scheduleren ble aktivert i `forge`-brukerens crontab:

```cron
* * * * * cd /home/forge/innsatt.no/innnsattno && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

Cron ble bekreftet kjørt hvert minutt gjennom systemloggen. Laravel kjører KDI hvert 15. minutt og NFF, KY og Sivilombudet hvert 30. minutt. Scheduleroppgavene bruker `withoutOverlapping()`.

### Senere kontroll

```bash
sudo -u forge crontab -l
sudo -u forge /usr/bin/php /home/forge/innsatt.no/innnsattno/artisan schedule:list
sudo -u forge /usr/bin/php /home/forge/innsatt.no/innnsattno/artisan correctional-news:fetch
```

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
