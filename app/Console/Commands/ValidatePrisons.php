<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ValidatePrisons extends Command
{
    protected $signature = 'prisons:validate';
    protected $description = 'Valider fengselsregisteret uten nettverkskall eller dataendringer';

    public function handle(): int
    {
        $prisons = config('prisons.all', []);
        $errors = [];
        $default = config('prisons.default');

        if (! is_array($prisons) || $prisons === []) {
            $errors[] = 'prisons.all må inneholde minst ett fengsel.';
        }
        if (! is_string($default) || ! array_key_exists($default, $prisons)) {
            $errors[] = 'prisons.default må peke på et fengsel i prisons.all.';
        }

        foreach ($prisons as $slug => $prison) {
            $prefix = "{$slug}: ";
            if (! is_string($slug) || preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) !== 1) {
                $errors[] = $prefix.'URL-ID må bestå av små bokstaver, tall og eventuelle bindestreker.';
            }
            if (! is_array($prison) || ! is_string($prison['name'] ?? null) || trim($prison['name']) === '') {
                $errors[] = $prefix.'visningsnavn mangler.';
                continue;
            }

            $features = $prison['features'] ?? null;
            foreach (['weather', 'local_news', 'tv', 'prayer', 'visitation'] as $feature) {
                if (! is_array($features) || ! is_bool($features[$feature] ?? null)) {
                    $errors[] = $prefix."features.{$feature} må være true eller false.";
                }
            }
            if (! is_array($features)) {
                continue;
            }
            if (($features['weather'] ?? false) && ! $this->validWeather($prison['weather'] ?? null)) {
                $errors[] = $prefix.'aktiv værmelding krever weather.name samt gyldig latitude og longitude.';
            }
            if (($features['tv'] ?? false)) {
                $channels = $prison['tv_channels'] ?? null;
                if (! is_array($channels) || $channels === []) {
                    $errors[] = $prefix.'aktiv TV-guide krever minst én TV-kanal.';
                } elseif (count($channels) !== count(array_unique($channels))) {
                    $errors[] = $prefix.'TV-kanalene kan ikke inneholde duplikater.';
                } else {
                    foreach ($channels as $channel) if (! is_string($channel) || trim($channel) === '') $errors[] = $prefix.'TV-kanalene må være ikke-tomme kanal-ID-er.';
                }
            }
            if (($features['prayer'] ?? false) && ! $this->positiveInteger($prison['prayer_location_id'] ?? null)) {
                $errors[] = $prefix.'aktive bønnetider krever en positiv prayer_location_id.';
            }
            if (($features['local_news'] ?? false) && ! $this->validLocalNews($prison['local_news'] ?? null)) {
                $errors[] = $prefix.'aktive lokale nyheter krever source_name, minst én HTTPS-kilde og tillatte artikkelverter.';
            }
            if (($features['visitation'] ?? false) && ! $this->validDepartments($prison['visitation_departments'] ?? null)) {
                $errors[] = $prefix.'aktiv visitasjonsrullett krever minst én avdeling med minst én verifisert celle.';
            }
        }

        if ($errors === []) {
            $this->info('Fengselsregisteret er gyldig ('.count($prisons).' fengsler).');
            return self::SUCCESS;
        }
        foreach ($errors as $error) {
            $this->error($error);
        }
        return self::FAILURE;
    }

    private function validWeather(mixed $weather): bool
    {
        return is_array($weather) && is_string($weather['name'] ?? null) && trim($weather['name']) !== ''
            && is_numeric($weather['latitude'] ?? null) && is_numeric($weather['longitude'] ?? null)
            && (float) $weather['latitude'] >= -90 && (float) $weather['latitude'] <= 90
            && (float) $weather['longitude'] >= -180 && (float) $weather['longitude'] <= 180;
    }

    private function positiveInteger(mixed $value): bool
    {
        return (is_int($value) || (is_string($value) && ctype_digit($value))) && (int) $value > 0;
    }

    private function validLocalNews(mixed $news): bool
    {
        if (! is_array($news) || ! is_string($news['source_name'] ?? null) || trim($news['source_name']) === '' || ! is_array($news['sources'] ?? null) || $news['sources'] === []) return false;
        foreach ($news['sources'] as $source) {
            if (! is_array($source) || ! filter_var($source['url'] ?? null, FILTER_VALIDATE_URL) || parse_url($source['url'], PHP_URL_SCHEME) !== 'https' || ! is_array($source['article_hosts'] ?? null) || $source['article_hosts'] === []) return false;
        }
        return true;
    }

    private function validDepartments(mixed $departments): bool
    {
        if (! is_array($departments) || $departments === []) return false;
        foreach ($departments as $cells) if (! is_array($cells) || $cells === []) return false;
        return true;
    }
}
