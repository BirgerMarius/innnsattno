<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use JsonException;
use RuntimeException;
use Throwable;
use UnexpectedValueException;

class TvGuideService
{
    private const ENDPOINT = 'https://tvguide.vg.no/backend/api/tv-schedule';
    private const FRESH_TTL_SECONDS = 3900;
    private const TIMEZONE = 'Europe/Oslo';
    private const UPCOMING_DAYS = 7;

    /**
     * Exact VG titles are intentional: slugs are not reliable for UEFA programming.
     * A fixture-shaped sportsEvent.name is still required before an item is shown.
     */
    private const FOOTBALL_COMPETITIONS = [
        'premier-league' => ['label' => 'Premier League', 'title' => 'Premier League'],
        'eliteserien' => ['label' => 'Eliteserien', 'title' => 'Eliteserien'],
        'champions-league' => ['label' => 'Champions League', 'title' => 'UEFA Champions League'],
        'europa-league' => ['label' => 'Europa League', 'title' => 'UEFA Europa League'],
        'conference-league' => ['label' => 'Conference League', 'title' => 'UEFA Conference League'],
        'nations-league' => ['label' => 'Nations League', 'title' => 'UEFA Nations League', 'allow_scheduled' => true, 'calendar_week' => true],
    ];

    /**
     * Competitions that have their own printable football page, plus the three
     * large European leagues which VG exposes in the same schedule feed.  The
     * titles are deliberately explicit: this keeps handball and studio shows
     * out even when their event name happens to look like a fixture.
     */
    private const WEEKLY_FOOTBALL_TITLES = [
        'Premier League' => 'Premier League',
        'Eliteserien' => 'Eliteserien',
        'UEFA Champions League' => 'Champions League',
        'UEFA Europa League' => 'Europa League',
        'UEFA Conference League' => 'Conference League',
        'UEFA Nations League' => 'Nations League',
        'FIFA World Cup' => 'Fotball-VM',
        'FIFA World Cup 2026' => 'Fotball-VM',
        'Fotball-VM' => 'Fotball-VM',
        'La Liga' => 'La Liga',
        'LaLiga' => 'La Liga',
        'LaLiga EA Sports' => 'La Liga',
        'Bundesliga' => 'Bundesliga',
        'Serie A' => 'Serie A',
        'Serie A Enilive' => 'Serie A',
        'OBOS-ligaen' => 'OBOS-ligaen',
    ];

    /**
     * VG changes presentation titles more often than stable title IDs/slugs.
     * Keep verified aliases here so sport programmes cannot enter solely due to
     * a dash in a name. IDs and slugs are used before human-readable aliases.
     */
    private const WEEKLY_FOOTBALL_IDENTIFIERS = [
        36804 => 'La Liga', // LaLiga EA Sports
        78710 => 'OBOS-ligaen',
    ];

    private const WEEKLY_FOOTBALL_SLUGS = [
        'la-liga' => 'La Liga',
        'obos-ligaen-1' => 'OBOS-ligaen',
    ];

    /**
     * Only add entries after verifying their exact VG representation.  This is
     * deliberately separate from the competition boxes above: a Norway match
     * is highlighted in the ordinary prison TV guide, not shown in a new box.
     */
    private const SUPPORTED_NORWAY_MENS_COMPETITIONS = [
        [
            'title_id' => 572756,
            'title_type' => 'sportsTitle',
            'title' => 'UEFA Nations League',
            'slug' => 'uefa-nations-league',
        ],
    ];

    public function __construct(private ExternalDataFailureNotifier $failureNotifier)
    {
    }

    public function getSchedule(CarbonInterface $date, array $channels, string $operation): array
    {
        $date = $date->copy()->setTimezone(self::TIMEZONE);
        $keys = $this->cacheKeys($date, $channels);
        $schedule = Cache::get($keys['fresh'], Cache::get($keys['stale']));
        return is_array($schedule) ? $schedule : [];
    }

    public function lastUpdated(CarbonInterface $date, array $channels): ?\Carbon\Carbon
    {
        $value = Cache::get($this->cacheKeys($date, $channels)['updated']);
        return is_string($value) ? \Carbon\Carbon::parse($value)->setTimezone(self::TIMEZONE) : null;
    }

    public function refreshDeferred(CarbonInterface $date, array $channels): bool
    {
        $date = $date->copy()->setTimezone(self::TIMEZONE);
        return Cache::has($this->cacheKeys($date, $channels)['updated'].'.unpublished');
    }

    /** True when the page can read a date-specific local schedule without fetching. */
    public function hasCachedSchedule(CarbonInterface $date, array $channels): bool
    {
        $date = $date->copy()->setTimezone(self::TIMEZONE);
        $keys = $this->cacheKeys($date, $channels);

        return is_array(Cache::get($keys['fresh'])) || is_array(Cache::get($keys['stale']));
    }

    /** A scheduled source check; the same lock protects all users of this data key. */
    public function refreshSchedule(CarbonInterface $date, array $channels, string $selection): bool
    {
        $date = $date->copy()->setTimezone(self::TIMEZONE)->startOfDay();
        $keys = $this->cacheKeys($date, $channels);
        $key = $selection.'-'.$date->format('Y-m-d');
        $lock = Cache::lock('fetch.'.$keys['stale'], 45);
        if (! $lock->get()) {
            return false;
        }
        try {
            $existing = Cache::get($keys['stale']);
            $essential = $date->isSameDay(now(self::TIMEZONE));
            try {
                $schedule = $this->fetch($date, $channels);
                // VG returns [] for dates not yet published. A missing current day is not usable.
                if ($essential && $schedule === []) {
                    throw new UnexpectedValueException('VG TV-guide mangler programmer for i dag.');
                }
                if ($schedule === [] && is_array($existing) && $existing !== []) {
                    // An unpublished future date does not supersede previously usable data.
                    // Back off without counting this response as a successful coverage refresh.
                    Cache::put($keys['updated'].'.unpublished', true, now()->addMinutes(15));
                    return true;
                }
                Cache::forget($keys['updated'].'.unpublished');
                $this->storeSchedule($keys, $schedule);
                $this->failureNotifier->reportSuccess('tv-guide-vg', $key);
                app(ExternalDataMonitor::class)->checked('tv-guide-vg', $key, 'tv', $selection.' – '.$date->format('Y-m-d').' (TV og fotball)', $essential, true, $schedule !== [], null, $date->copy()->endOfDay()->setTimezone(config('app.timezone')));
                return true;
            } catch (Throwable $exception) {
                $this->failureNotifier->report('tv-guide-vg', $this->summary($exception), [
                    'operation' => $key, 'date' => $date->format('Y-m-d'),
                    'failure_kind' => $this->failureKind($exception),
                    'status' => $exception instanceof RequestException ? $exception->response?->status() : null,
                ]);
                app(ExternalDataMonitor::class)->checked('tv-guide-vg', $key, 'tv', $selection.' – '.$date->format('Y-m-d').' (TV og fotball)', $essential, false, is_array($existing) && $existing !== [], $this->summary($exception), $date->copy()->endOfDay()->setTimezone(config('app.timezone')));
                return false;
            }
        } finally {
            $lock->release();
        }
    }

    /**
     * Add a presentation-safe title without changing the cached VG payload.
     */
    public function withDisplayTitles(array $schedule): array
    {
        foreach ($schedule as &$channel) {
            if (!is_array($channel) || !is_array($channel['listings'] ?? null)) {
                continue;
            }

            foreach ($channel['listings'] as &$listing) {
                if (is_array($listing)) {
                    $listing['displayTitle'] = $this->displayTitle($listing);
                    $listing['norwayMensMatchLabel'] = $this->norwayMensMatchLabel($listing);
                }
            }
            unset($listing);
        }
        unset($channel);

        return $schedule;
    }

    public function displayTitle(array $listing): string
    {
        $title = trim((string) data_get($listing, 'title.title', ''));
        $eventName = trim((string) data_get($listing, 'sportsEvent.name', ''));

        if ($title === '' || $eventName === '' || $this->titleAlreadyContainsEvent($title, $eventName)) {
            return $title;
        }

        // VG may put a programme category in title and the fuller category plus
        // season in sportsEvent.name ("...: Magasin" + "Magasin 2026/27").
        if (preg_match('/^(.+?):\s*(.+)$/u', $title, $parts) === 1
            && Str::startsWith(Str::lower($this->formatEventName($eventName)), Str::lower(trim($parts[2])).' ')) {
            return trim($parts[1]).': '.$this->formatEventName($eventName);
        }

        return $title.': '.$this->formatEventName($eventName);
    }

    /**
     * A label for a verified Norway men's fixture, or null for all other
     * programmes.  Do not require isLive: VG has marked a scheduled first
     * broadcast as false before kick-off.  Replays remain excluded.
     */
    public function norwayMensMatchLabel(array $listing): ?string
    {
        if (($listing['isRerun'] ?? false) === true || ! $this->hasSupportedNorwayMensCompetition($listing)) {
            return null;
        }

        $participants = $this->fixtureParticipants((string) data_get($listing, 'sportsEvent.name', ''));

        if ($participants === null || ! in_array('Norge', $participants, true)) {
            return null;
        }

        return '⚽ '.implode(' – ', $participants);
    }

    public static function ringerikeChannels(): array
    {
        return self::channelsForPrison('ringerike');
    }

    /** The existing Ilseng selection used by its TV guide printout. */
    public static function ilsengChannels(): array
    {
        return self::channelsForPrison('ilseng');
    }

    public static function channelsForPrison(string $prison): array
    {
        $definition = config("prisons.all.{$prison}");
        if (! is_array($definition)) throw new UnexpectedValueException('Ukjent fengsel for TV-guiden.');

        return array_values($definition['tv_channels'] ?? []);
    }

    /**
     * Return direct, identifiable football matches for one supported competition.
     * The cache is shared between all football pages and printouts. Nations
     * League callers can opt into an exact rolling seven-day period; its
     * default remains the calendar week for the existing team-page use.
     */
    public function getUpcomingCompetitionMatches(CarbonInterface $now, string $competition, string $operation, int $limit, bool $useRollingSevenDayPeriod = false, string $prison = 'ringerike'): array
    {
        if (!isset(self::FOOTBALL_COMPETITIONS[$competition])) {
            throw new UnexpectedValueException('Ukjent fotballturnering for TV-oversikten.');
        }

        $definition = self::FOOTBALL_COMPETITIONS[$competition];
        $now = $now->copy()->setTimezone(self::TIMEZONE);
        $useCalendarWeek = ($definition['calendar_week'] ?? false) && ! $useRollingSevenDayPeriod;
        $periodStart = $useCalendarWeek
            ? $now->copy()->startOfWeek(\Carbon\Carbon::MONDAY)
            : $now->copy();
        $end = $useCalendarWeek
            ? $now->copy()->endOfWeek(\Carbon\Carbon::SUNDAY)
            : ($useRollingSevenDayPeriod
                ? $now->copy()->addDays(self::UPCOMING_DAYS)
                : $now->copy()->addDays(self::UPCOMING_DAYS - 1)->endOfDay());
        $dates = collect(range(0, $periodStart->diffInDays($end)))
            ->map(fn (int $offset) => $periodStart->copy()->addDays($offset)->startOfDay())
            ->all();
        $channels = self::channelsForPrison($prison);
        if ($channels === []) return ['competitionLabel' => $definition['label'], 'channelSelection' => 'Kongsvinger fengsel – under klargjøring', 'matches' => [], 'hasSourceFailure' => false, 'usingStaleData' => false, 'missingMatchNames' => 0, 'excludedNonMatchProgrammes' => 0, 'periodEnd' => $end, 'tvPending' => true];
        $schedules = [];
        $hasSourceFailure = false;
        $usingStaleData = false;

        foreach ($dates as $date) {
            $keys = $this->cacheKeys($date, $channels);
            $fresh = Cache::get($keys['fresh']);
            if (is_array($fresh)) {
                $schedules[] = $fresh;
                continue;
            }
            $stale = Cache::get($keys['stale']);
            if (is_array($stale)) {
                $schedules[] = $stale;
                $usingStaleData = true;
            } else {
                $hasSourceFailure = true;
            }
        }

        $matches = [];
        $missingMatchNames = 0;
        $excludedNonMatchProgrammes = 0;
        foreach ($schedules as $schedule) {
            foreach ($schedule as $channel) {
                foreach (($channel['listings'] ?? []) as $listing) {
                    if (!$this->isCompetitionBroadcast($listing, $definition)) {
                        continue;
                    }
                    $startsAt = data_get($listing, 'startsAt');
                    if (!is_string($startsAt) || $startsAt === '') {
                        continue;
                    }
                    $startsAt = \Carbon\Carbon::parse($startsAt)->setTimezone(self::TIMEZONE);
                    if ($startsAt->lessThan($periodStart) || $startsAt->greaterThan($end)) {
                        continue;
                    }
                    $eventName = trim((string) data_get($listing, 'sportsEvent.name', ''));
                    if ($eventName === '') {
                        $missingMatchNames++;
                        continue;
                    }
                    if (!$this->looksLikeFixtureName($eventName)) {
                        $excludedNonMatchProgrammes++;
                        continue;
                    }
                    $matches[(string) data_get($listing, 'sportsEvent.id', $startsAt->toIso8601String().'|'.$eventName)] = [
                        'name' => $this->formatEventName($eventName),
                        'startsAt' => $startsAt,
                        'channel' => trim((string) data_get($channel, 'channel.name', 'TV-kanal')),
                    ];
                }
            }
        }
        usort($matches, fn (array $left, array $right) => $left['startsAt']->getTimestamp() <=> $right['startsAt']->getTimestamp());

        return [
            'competitionLabel' => $definition['label'],
            'matches' => $limit > 0 ? array_slice(array_values($matches), 0, $limit) : array_values($matches),
            'channelSelection' => config("prisons.all.{$prison}.name", 'Ukjent fengsel'),
            'hasSourceFailure' => $hasSourceFailure,
            'usingStaleData' => $usingStaleData,
            'missingMatchNames' => $missingMatchNames,
            'excludedNonMatchProgrammes' => $excludedNonMatchProgrammes,
            'periodEnd' => $end,
        ];
    }

    /** Backwards-compatible entry point used by the existing Premier League pages. */
    public function getUpcomingPremierLeagueOnTv3Plus(CarbonInterface $now, string $operation, int $limit, bool $useRollingSevenDayPeriod = false, string $prison = 'ringerike'): array
    {
        return $this->getUpcomingCompetitionMatches($now, 'premier-league', $operation, $limit, $useRollingSevenDayPeriod, $prison);
    }

    /**
     * Whole football matches in the ISO calendar week, plus the night to the
     * following Monday until 06:00.  TV-guide start time is authoritative;
     * SportsNext is deliberately not required for an entry to be shown.
     */
    public function getWeeklyFootballMatches(CarbonInterface $now, string $prison, string $operation, int $weekOffset = 0): array
    {
        $now = $now->copy()->setTimezone(self::TIMEZONE);
        $weekOffset = $weekOffset === 1 ? 1 : 0;
        $weekStart = $now->copy()->startOfWeek(\Carbon\Carbon::MONDAY)->addWeeks($weekOffset);
        $weekEnd = $weekStart->copy()->endOfWeek(\Carbon\Carbon::SUNDAY);
        $channels = self::channelsForPrison($prison);
        if ($channels === []) return ['weekStart' => $weekStart, 'weekEnd' => $weekEnd, 'matches' => [], 'mondayNightMatches' => [], 'availableDates' => [], 'missingLocalDataDates' => [], 'unpublishedDates' => [], 'hasMissingLocalData' => false, 'hasUnpublishedDates' => false, 'usingStaleData' => false, 'weekOffset' => $weekOffset, 'channelSelection' => 'Kongsvinger fengsel – under klargjøring', 'lastUpdated' => null, 'tvPending' => true];
        // VG overlaps adjacent calendar dates around midnight. Read Monday too
        // so the optional night section is complete, then deduplicate a concrete
        // listing rather than a sporting event.
        $dates = collect(range(0, 7))->map(fn (int $offset) => $weekStart->copy()->addDays($offset))->all();
        $matches = [];
        $availableDates = [];
        $missingLocalDataDates = [];
        $unpublishedDates = [];
        $usingStaleData = false;
        $updatedAt = [];

        foreach ($dates as $date) {
            $keys = $this->cacheKeys($date, $channels);
            $schedule = Cache::get($keys['fresh']);
            $fromStale = false;

            if (! is_array($schedule)) {
                $schedule = Cache::get($keys['stale']);
                $fromStale = is_array($schedule);
                $usingStaleData = $usingStaleData || $fromStale;
                if (! $fromStale) {
                    // A page view only reads local data.  A missing cache entry
                    // does not prove that the source fetch for this date failed.
                    if ($date->lessThanOrEqualTo($weekEnd)) $missingLocalDataDates[] = $date;
                    continue;
                }
            }

            $timestamp = Cache::get($keys['updated']);
            if (is_string($timestamp)) {
                $updatedAt[] = \Carbon\Carbon::parse($timestamp)->setTimezone(self::TIMEZONE);
            }

            // VG answers with an empty root array outside its published date
            // window. It is a valid payload, but not evidence that no match is
            // on TV, so report it as unavailable coverage instead.
            if ($schedule === []) {
                if ($date->lessThanOrEqualTo($weekEnd)) $unpublishedDates[] = $date;
                continue;
            }

            if ($date->lessThanOrEqualTo($weekEnd)) $availableDates[] = $date;
            foreach ($schedule as $channel) {
                $channelName = trim((string) data_get($channel, 'channel.name', ''));
                $channelSlug = trim((string) data_get($channel, 'channel.slug', ''));
                if ($channelName === '') {
                    continue;
                }
                foreach (($channel['listings'] ?? []) as $listing) {
                    $competition = $this->weeklyFootballCompetition($listing);
                    if ($competition === null || ! $this->isWeeklyFootballFixture($listing)) {
                        continue;
                    }
                    $startsAtValue = data_get($listing, 'startsAt');
                    if (! is_string($startsAtValue) || $startsAtValue === '') {
                        continue;
                    }
                    $startsAt = \Carbon\Carbon::parse($startsAtValue)->setTimezone(self::TIMEZONE);
                    $nightToMonday = $startsAt->isSameDay($weekEnd->copy()->addDay())
                        && $startsAt->format('H:i:s') < '06:00:00';
                    if (($startsAt->lessThan($weekStart) || $startsAt->greaterThan($weekEnd)) && ! $nightToMonday) {
                        continue;
                    }
                    $name = $this->formatEventName((string) data_get($listing, 'sportsEvent.name'));
                    $key = $this->weeklyListingKey($listing, $channelSlug, $startsAt);
                    if (! isset($matches[$key])) {
                        $matches[$key] = [
                            'name' => $name,
                            'competition' => $competition,
                            'startsAt' => $startsAt,
                            'channels' => [],
                            'broadcastType' => $this->broadcastType($listing),
                            'nightToMonday' => $nightToMonday,
                        ];
                    }
                    $matches[$key]['channels'][$channelName] = $channelName;
                }
            }
        }

        $matches = array_values(array_map(function (array $match): array {
            $match['channels'] = array_values($match['channels']);
            sort($match['channels'], SORT_NATURAL | SORT_FLAG_CASE);
            return $match;
        }, $matches));
        usort($matches, fn (array $left, array $right) => ($left['nightToMonday'] <=> $right['nightToMonday'])
            ?: ($left['startsAt']->getTimestamp() <=> $right['startsAt']->getTimestamp()));

        $mondayNightMatches = array_values(array_filter($matches, fn (array $match) => $match['nightToMonday']));
        $matches = array_values(array_filter($matches, fn (array $match) => ! $match['nightToMonday']));

        return [
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'matches' => $matches,
            'mondayNightMatches' => $mondayNightMatches,
            'availableDates' => $availableDates,
            'missingLocalDataDates' => $missingLocalDataDates,
            'unpublishedDates' => $unpublishedDates,
            'hasMissingLocalData' => $missingLocalDataDates !== [],
            'hasUnpublishedDates' => $unpublishedDates !== [],
            'usingStaleData' => $usingStaleData,
            'weekOffset' => $weekOffset,
            'channelSelection' => $prison === 'ilseng' ? 'Ilseng fengsel' : 'Ringerike fengsel',
            // With partial coverage this is the oldest timestamp of the data
            // being shown.  The separate coverage warning makes clear that it
            // does not describe dates which are missing locally.
            'lastUpdated' => $updatedAt !== [] ? collect($updatedAt)->min() : null,
            'tvPending' => false,
        ];
    }

    private function fetch(CarbonInterface $date, array $channels): array
    {
        $response = null;

        for ($attempt = 1; $attempt <= 2; $attempt++) {
            try {
                $response = Http::acceptJson()
                    ->connectTimeout(3)
                    ->timeout(10)
                    ->get(self::ENDPOINT, [
                        'channels' => implode(',', $channels),
                        'date' => $date->format('Y-m-d'),
                        'tz' => self::TIMEZONE,
                    ]);

                if (! $response->serverError() || $attempt === 2) {
                    break;
                }
            } catch (ConnectionException $exception) {
                if ($attempt === 2) {
                    throw $exception;
                }
            }

            usleep(250000);
        }

        if ($response === null) {
            throw new ConnectionException('VG TV-guide svarte ikke.');
        }

        $response->throw();

        try {
            $payload = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new UnexpectedValueException('VG TV-guide returnerte ugyldig JSON.', 0, $exception);
        }

        if (! $this->isValidSchedule($payload)) {
            throw new UnexpectedValueException('VG TV-guide mangler forventede programdata.');
        }

        return $payload;
    }

    private function isCompetitionBroadcast(mixed $listing, array $definition): bool
    {
        return is_array($listing)
            && data_get($listing, 'title.type') === 'sportsTitle'
            && data_get($listing, 'title.title') === $definition['title']
            && ($listing['isRerun'] ?? false) !== true
            && (($definition['allow_scheduled'] ?? false) || ($listing['isLive'] ?? false) === true);
    }

    private function weeklyFootballCompetition(mixed $listing): ?string
    {
        if (! is_array($listing) || data_get($listing, 'title.type') !== 'sportsTitle') {
            return null;
        }

        $titleId = (int) data_get($listing, 'title.id', 0);
        if (isset(self::WEEKLY_FOOTBALL_IDENTIFIERS[$titleId])) {
            return self::WEEKLY_FOOTBALL_IDENTIFIERS[$titleId];
        }

        $slug = (string) data_get($listing, 'title.slug', '');
        if (isset(self::WEEKLY_FOOTBALL_SLUGS[$slug])) {
            return self::WEEKLY_FOOTBALL_SLUGS[$slug];
        }

        return self::WEEKLY_FOOTBALL_TITLES[(string) data_get($listing, 'title.title', '')] ?? null;
    }

    private function isWeeklyFootballFixture(array $listing): bool
    {
        $eventName = trim((string) data_get($listing, 'sportsEvent.name', ''));

        return $this->looksLikeFixtureName($eventName)
            && preg_match('/\b(studio|magasin|høydepunkter|highlights|oppsummering)\b/ui', $eventName) !== 1;
    }

    private function broadcastType(array $listing): string
    {
        if (($listing['isRerun'] ?? false) === true) return 'Reprise';
        if (($listing['isLive'] ?? false) === true) return 'Direkte';

        return 'Sendetype ikke oppgitt';
    }

    private function weeklyListingKey(array $listing, string $channelSlug, \Carbon\CarbonInterface $startsAt): string
    {
        $listingId = data_get($listing, 'id');
        if (is_scalar($listingId) && (string) $listingId !== '') return 'listing:'.(string) $listingId;

        $programmeId = data_get($listing, 'title.id')
            ?? data_get($listing, 'simplyTvId')
            ?? data_get($listing, 'sportsEvent.id')
            ?? data_get($listing, 'title.slug', 'unknown');

        return 'fallback:'.sha1($channelSlug.'|'.$startsAt->toIso8601String().'|'.(string) $programmeId);
    }

    private function looksLikeFixtureName(string $eventName): bool
    {
        return $this->fixtureParticipants($eventName) !== null;
    }

    /** @return array{string, string}|null */
    private function fixtureParticipants(string $eventName): ?array
    {
        if (preg_match('/^\s*([^–-]+?)\s+[–-]\s+([^–-]+?)\s*$/u', $eventName, $matches) !== 1) {
            return null;
        }

        $home = trim($matches[1]);
        $away = trim($matches[2]);

        return $home !== '' && $away !== '' ? [$home, $away] : null;
    }

    private function hasSupportedNorwayMensCompetition(array $listing): bool
    {
        foreach (self::SUPPORTED_NORWAY_MENS_COMPETITIONS as $competition) {
            if ((int) data_get($listing, 'title.id') === $competition['title_id']
                && data_get($listing, 'title.type') === $competition['title_type']
                && data_get($listing, 'title.title') === $competition['title']
                && data_get($listing, 'title.slug') === $competition['slug']) {
                return true;
            }
        }

        return false;
    }

    private function formatEventName(string $eventName): string
    {
        return preg_replace('/\s+-\s+/u', ' – ', trim($eventName)) ?? trim($eventName);
    }

    private function titleAlreadyContainsEvent(string $title, string $eventName): bool
    {
        return Str::contains(
            Str::lower($this->formatEventName($title)),
            Str::lower($this->formatEventName($eventName)),
        );
    }

    private function isValidSchedule(mixed $payload): bool
    {
        if (! is_array($payload) || ($payload !== [] && array_keys($payload) !== range(0, count($payload) - 1))) {
            return false;
        }

        foreach ($payload as $channel) {
            if (! is_array($channel) || ! is_array($channel['channel'] ?? null) || ! is_array($channel['listings'] ?? null)) {
                return false;
            }
        }

        return true;
    }

    private function cacheKeys(CarbonInterface $date, array $channels): array
    {
        $identity = $date->format('Y-m-d').'|'.self::TIMEZONE.'|'.implode(',', $channels);
        $suffix = $date->format('Y-m-d').'.'.sha1($identity);

        return [
            'fresh' => 'tv-guide-vg.fresh.'.$suffix,
            'stale' => 'tv-guide-vg.stale.'.$suffix,
            'updated' => 'tv-guide-vg.updated.'.$suffix,
        ];
    }

    /**
     * A successful source response is only a successful refresh once every
     * local value needed by readers has been persisted. Cache stores may
     * signal a write failure by returning false rather than throwing.
     */
    private function storeSchedule(array $keys, array $schedule): void
    {
        $updatedAt = now()->toIso8601String();
        $freshStored = Cache::put($keys['fresh'], $schedule, now()->addSeconds(self::FRESH_TTL_SECONDS));
        $staleStored = Cache::forever($keys['stale'], $schedule);
        $updatedStored = Cache::forever($keys['updated'], $updatedAt);

        if (! $freshStored || ! $staleStored || ! $updatedStored || Cache::get($keys['stale']) !== $schedule) {
            throw new RuntimeException('TV-guide-data kunne ikke lagres i lokal cache.');
        }
    }

    private function failureKind(Throwable $exception): string
    {
        if ($exception instanceof ConnectionException) {
            return 'connection_exception';
        }

        if ($exception instanceof RequestException) {
            return 'http_status';
        }

        if ($exception instanceof JsonException) {
            return 'invalid_json';
        }

        if ($exception instanceof UnexpectedValueException && $exception->getPrevious() instanceof JsonException) {
            return 'invalid_json';
        }

        if ($exception instanceof UnexpectedValueException) {
            return 'invalid_payload';
        }

        return 'unexpected_error';
    }

    private function summary(Throwable $exception): string
    {
        if ($exception instanceof RequestException) {
            return 'VG TV-guide svarte med HTTP-status '.$exception->response?->status().'.';
        }

        if ($exception instanceof ConnectionException) {
            return 'VG TV-guide kunne ikke nås.';
        }

        if ($exception instanceof UnexpectedValueException) {
            return $exception->getMessage();
        }

        return 'VG TV-guide kunne ikke hentes.';
    }
}
