<?php

namespace App\Console\Commands;

use App\NewsSource;
use App\Services\CorrectionalNewsService;
use App\Services\NamedayService;
use App\Services\OnThisDayService;
use App\Services\TodayContentService;
use App\Services\News\NewsFeedService;
use App\Services\PrayerTimeService;
use App\Services\LocalNewsService;
use App\Services\WeatherForecastService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CheckOtherExternalData extends Command
{
    protected $signature = 'external-data:check-others';
    protected $description = 'Oppdater og kontroller øvrige eksterne datakilder';

    public function handle(): int
    {
        if (! Schema::hasTable('external_data_checks')) return self::FAILURE;
        foreach (['ringerike', 'ilseng', 'kongsvinger'] as $location) {
            if ($this->due('met-weather', $location)) app(WeatherForecastService::class)->refresh($location);
        }
        $today = now('Europe/Oslo')->startOfDay();
        $incident = DB::table('external_data_incidents')->where('service', 'bonnetid-no')->first();
        $alerted = $incident ? (json_decode($incident->affected_keys, true) ?: []) : [];
        foreach (collect(config('prisons.all'))->pluck('prayer_location_id')->filter(fn ($id) => is_int($id) || ctype_digit((string) $id)) as $location) {
            $months = [$today->copy()->startOfMonth()];
            if ($today->copy()->startOfMonth()->addMonth()->lte($today->copy()->addDays(7))) $months[] = $today->copy()->startOfMonth()->addMonth();
            foreach ($alerted as $key) {
                if (preg_match('/^'.preg_quote((string) $location, '/').'-(\d{4}-\d{2})-\d{2}$/', $key, $match)) $months[] = Carbon::parse($match[1].'-01', 'Europe/Oslo');
            }
            $months = collect($months)->unique(fn ($month) => $month->format('Y-m'));
            foreach ($months as $month) {
                $prefix = $location.'-'.$month->format('Y-m').'-';
                $rows = DB::table('external_data_checks')->where('service', 'bonnetid-no')->where('coverage_key', 'like', $prefix.'%')->get();
                $last = $rows->max('last_check_at');
                $failed = $rows->contains(fn ($row) => $row->failure_count > 0);
                $missingToday = $rows->contains(fn ($row) => $row->essential && ! $row->usable && $row->failure_count > 0);
                if (! $last || Carbon::parse($last)->lte(now()->subMinutes($missingToday ? 5 : ($failed ? 15 : 60)))) {
                    app(PrayerTimeService::class)->refreshMonth($location, $month->year, $month->month);
                }
            }
        }
        foreach (array_keys(config('prisons.all', [])) as $prison) {
            if (app(LocalNewsService::class)->needsRefresh($prison, fn (string $service, string $key) => $this->due($service, $key))) {
                app(LocalNewsService::class)->refresh($prison);
            }
        }
        if ($this->due('namedays-webapi', 'today')) app(NamedayService::class)->refresh($today);
        if (! \Illuminate\Support\Facades\Cache::has('external-data.today.attempt') && ($this->due('wikipedia-on-this-day', 'no-mediawiki') || $this->due('wikipedia-on-this-day', 'no-onthisday') || $this->due('wikipedia-on-this-day', 'en-onthisday') || $this->due('wikidata-entities', 'today-content-enrichment'))) {
            $lock = \Illuminate\Support\Facades\Cache::lock('fetch.today-sources', 180);
            if ($lock->get()) {
                try {
                    \Illuminate\Support\Facades\Cache::put('external-data.today.attempt', true, now()->addMinutes(15));
                    app(TodayContentService::class)->forDate($today, true);
                    app(OnThisDayService::class)->checkSources($today);
                } finally { $lock->release(); }
            }
        }
        foreach (array_keys(config('correctional_news.sources', [])) as $key) {
            if ($this->due('correctional-news-'.$key, 'front-page')) app(CorrectionalNewsService::class)->refresh($key);
        }
        if (Schema::hasTable('news_sources')) {
            foreach (NewsSource::where('is_active', true)->get() as $source) {
                if ($this->due('news-feed-'.$source->slug, 'news')) app(NewsFeedService::class)->fetch($source);
            }
        }
        return self::SUCCESS;
    }

    private function due(string $service, string $key): bool
    {
        $row = DB::table('external_data_checks')->where('service', $service)->where('coverage_key', $key)->first();
        return ! $row || ! $row->last_check_at || Carbon::parse($row->last_check_at)->lte(now()->subMinutes($row->failure_count ? 15 : 60));
    }
}
