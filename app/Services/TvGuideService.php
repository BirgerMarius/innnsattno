<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use JsonException;
use Throwable;
use UnexpectedValueException;

class TvGuideService
{
    private const ENDPOINT = 'https://tvguide.vg.no/backend/api/tv-schedule';
    private const FRESH_TTL_SECONDS = 600;
    private const STALE_TTL_SECONDS = 259200;
    private const TIMEZONE = 'Europe/Oslo';
    private const UPCOMING_DAYS = 7;

    /** The existing Ringerike selection used by the TV guide and football boxes. */
    private const RINGERIKE_CHANNELS = [
        'nrk1', 'nrk2', 'nrk3', 'tv2-direkte', 'tv2-zebra', 'tvnorge', 'tv3', 'tv3-plus',
        'tv2-sport-1', 'tv2-sport-2', 'eurosport-norge', 'eurosport-1', 'c-more-hits',
        'tv2-livsstil', 'rex', 'fem', 'national-geographic', 'discovery-channel',
        'viasat-explore', 'investigation-discovery', 'bbc-world-news', 'al-jazeera-english',
        'nickelodeon', 'dr1', 'mtv',
    ];

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
        'Bundesliga' => 'Bundesliga',
        'Serie A' => 'Serie A',
        'Serie A Enilive' => 'Serie A',
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

        $fresh = Cache::get($keys['fresh']);
        if (is_array($fresh)) {
            return $fresh;
        }

        try {
            $schedule = $this->fetch($date, $channels);

            Cache::put($keys['fresh'], $schedule, now()->addSeconds(self::FRESH_TTL_SECONDS));
            Cache::put($keys['stale'], $schedule, now()->addSeconds(self::STALE_TTL_SECONDS));
            $this->failureNotifier->reportSuccess('tv-guide-vg', $operation);

            return $schedule;
        } catch (Throwable $exception) {
            $this->failureNotifier->report('tv-guide-vg', $this->summary($exception), [
                'operation' => $operation,
                'failure_kind' => $this->failureKind($exception),
                'status' => $exception instanceof RequestException ? $exception->response?->status() : null,
            ]);

            $stale = Cache::get($keys['stale']);

            return is_array($stale) ? $stale : [];
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
        return self::RINGERIKE_CHANNELS;
    }

    /** The existing Ilseng selection used by its TV guide printout. */
    public static function ilsengChannels(): array
    {
        return [
            'nrk1', 'nrk2', 'nrk3', 'tv2-direkte', 'tv2-zebra', 'tv2-livsstil', 'tv2-nyheter',
            'tvnorge', 'tv3', 'tv3-plus', 'tv6', 'fem', 'rex', 'vox', 'discovery-channel',
            'national-geographic', 'eurosport-1', 'eurosport-norge', 'tv2-sport-1', 'tv2-sport-2',
            'v-sport-1', 'v-sport-2', 'v-sport-3', 'v-film-premiere', 'v-film-action', 'v-series',
            'bbc-nordic', 'disney-channel', 'history', 'tlc',
        ];
    }

    /**
     * Return direct, identifiable football matches for one supported competition.
     * The cache is shared between all football pages and printouts. Nations
     * League callers can opt into an exact rolling seven-day period; its
     * default remains the calendar week for the existing team-page use.
     */
    public function getUpcomingCompetitionMatches(CarbonInterface $now, string $competition, string $operation, int $limit, bool $useRollingSevenDayPeriod = false): array
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
        $channels = self::ringerikeChannels();
        $schedules = [];
        $missing = [];
        $hasSourceFailure = false;
        $usingStaleData = false;

        foreach ($dates as $date) {
            $keys = $this->cacheKeys($date, $channels);
            $fresh = Cache::get($keys['fresh']);
            if (is_array($fresh)) {
                $schedules[] = $fresh;
                continue;
            }
            $missing[$date->format('Y-m-d')] = ['date' => $date, 'keys' => $keys];
        }

        foreach ($this->fetchMany(array_column($missing, 'date'), $channels) as $dateKey => $result) {
            $keys = $missing[$dateKey]['keys'];
            if ($result['schedule'] !== null) {
                Cache::put($keys['fresh'], $result['schedule'], now()->addSeconds(self::FRESH_TTL_SECONDS));
                Cache::put($keys['stale'], $result['schedule'], now()->addSeconds(self::STALE_TTL_SECONDS));
                $this->failureNotifier->reportSuccess('tv-guide-vg', $operation);
                $schedules[] = $result['schedule'];
                continue;
            }

            $hasSourceFailure = true;
            $exception = $result['exception'];
            $this->failureNotifier->report('tv-guide-vg', $this->summary($exception), [
                'operation' => $operation,
                'failure_kind' => $this->failureKind($exception),
                'status' => $exception instanceof RequestException ? $exception->response?->status() : null,
            ]);
            $stale = Cache::get($keys['stale']);
            if (is_array($stale)) {
                $schedules[] = $stale;
                $usingStaleData = true;
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
            'hasSourceFailure' => $hasSourceFailure,
            'usingStaleData' => $usingStaleData,
            'missingMatchNames' => $missingMatchNames,
            'excludedNonMatchProgrammes' => $excludedNonMatchProgrammes,
            'periodEnd' => $end,
        ];
    }

    /** Backwards-compatible entry point used by the existing Premier League pages. */
    public function getUpcomingPremierLeagueOnTv3Plus(CarbonInterface $now, string $operation, int $limit): array
    {
        return $this->getUpcomingCompetitionMatches($now, 'premier-league', $operation, $limit);
    }

    /**
     * All verified live football broadcasts in the ISO calendar week containing
     * $now.  Unlike the small competition boxes, past days remain included so
     * the public page is a real week overview rather than a rolling window.
     */
    public function getWeeklyFootballMatches(CarbonInterface $now, string $prison, string $operation, int $weekOffset = 0): array
    {
        $now = $now->copy()->setTimezone(self::TIMEZONE);
        $weekOffset = $weekOffset === 1 ? 1 : 0;
        $weekStart = $now->copy()->startOfWeek(\Carbon\Carbon::MONDAY)->addWeeks($weekOffset);
        $weekEnd = $weekStart->copy()->endOfWeek(\Carbon\Carbon::SUNDAY);
        $channels = $prison === 'ilseng' ? self::ilsengChannels() : self::ringerikeChannels();
        $dates = collect(range(0, 6))->map(fn (int $offset) => $weekStart->copy()->addDays($offset))->all();
        $matches = [];
        $availableDates = [];
        $failedDates = [];
        $unpublishedDates = [];
        $usingStaleData = false;

        foreach ($dates as $date) {
            $keys = $this->cacheKeys($date, $channels);
            $schedule = Cache::get($keys['fresh']);
            $fromStale = false;

            if (! is_array($schedule)) {
                try {
                    $schedule = $this->fetch($date, $channels);
                    Cache::put($keys['fresh'], $schedule, now()->addSeconds(self::FRESH_TTL_SECONDS));
                    Cache::put($keys['stale'], $schedule, now()->addSeconds(self::STALE_TTL_SECONDS));
                    $this->failureNotifier->reportSuccess('tv-guide-vg', $operation);
                } catch (Throwable $exception) {
                    $this->failureNotifier->report('tv-guide-vg', $this->summary($exception), [
                        'operation' => $operation,
                        'failure_kind' => $this->failureKind($exception),
                        'status' => $exception instanceof RequestException ? $exception->response?->status() : null,
                    ]);
                    $schedule = Cache::get($keys['stale']);
                    $fromStale = is_array($schedule);
                    $usingStaleData = $usingStaleData || $fromStale;
                    if (! $fromStale) {
                        $failedDates[] = $date;
                        continue;
                    }
                }
            }

            // VG answers with an empty root array outside its published date
            // window. It is a valid payload, but not evidence that no match is
            // on TV, so report it as unavailable coverage instead.
            if ($schedule === []) {
                $unpublishedDates[] = $date;
                continue;
            }

            $availableDates[] = $date;
            foreach ($schedule as $channel) {
                $channelName = trim((string) data_get($channel, 'channel.name', ''));
                if ($channelName === '') {
                    continue;
                }
                foreach (($channel['listings'] ?? []) as $listing) {
                    $competition = $this->weeklyFootballCompetition($listing);
                    if ($competition === null || ! $this->isWeeklyLiveFixture($listing)) {
                        continue;
                    }
                    $startsAtValue = data_get($listing, 'startsAt');
                    if (! is_string($startsAtValue) || $startsAtValue === '') {
                        continue;
                    }
                    $startsAt = \Carbon\Carbon::parse($startsAtValue)->setTimezone(self::TIMEZONE);
                    if ($startsAt->lessThan($weekStart) || $startsAt->greaterThan($weekEnd)) {
                        continue;
                    }
                    $name = $this->formatEventName((string) data_get($listing, 'sportsEvent.name'));
                    $key = (string) data_get($listing, 'sportsEvent.id', '');
                    $key = $key !== '' ? 'id:'.$key : 'fixture:'.Str::lower($competition.'|'.$name);
                    if (! isset($matches[$key])) {
                        $matches[$key] = [
                            'name' => $name,
                            'competition' => $competition,
                            'startsAt' => $startsAt,
                            'channels' => [],
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
        usort($matches, fn (array $left, array $right) => $left['startsAt']->getTimestamp() <=> $right['startsAt']->getTimestamp());

        return [
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'matches' => $matches,
            'availableDates' => $availableDates,
            'sourceFailureDates' => $failedDates,
            'unpublishedDates' => $unpublishedDates,
            'hasSourceFailure' => $failedDates !== [],
            'hasUnpublishedDates' => $unpublishedDates !== [],
            'usingStaleData' => $usingStaleData,
            'weekOffset' => $weekOffset,
            'channelSelection' => $prison === 'ilseng' ? 'Ilseng fengsel' : 'Ringerike fengsel',
            'lastUpdated' => now(self::TIMEZONE),
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

    private function fetchMany(array $dates, array $channels): array
    {
        $pending = [];
        foreach ($dates as $date) {
            $pending[$date->format('Y-m-d')] = $date;
        }
        $results = [];

        for ($attempt = 1; $attempt <= 2 && $pending !== []; $attempt++) {
            $responses = Http::pool(function (Pool $pool) use ($pending, $channels) {
                foreach ($pending as $key => $date) {
                    $pool->as($key)
                        ->acceptJson()
                        ->connectTimeout(3)
                        ->timeout(10)
                        ->get(self::ENDPOINT, [
                            'channels' => implode(',', $channels),
                            'date' => $date->format('Y-m-d'),
                            'tz' => self::TIMEZONE,
                        ]);
                }
            });

            $retry = [];
            foreach ($pending as $key => $date) {
                $response = $responses[$key] ?? null;
                try {
                    if (!$response instanceof Response) {
                        if ($attempt < 2) {
                            $retry[$key] = $date;
                            continue;
                        }
                        throw new ConnectionException('VG TV-guide svarte ikke.');
                    }
                    if ($response->serverError() && $attempt < 2) {
                        $retry[$key] = $date;
                        continue;
                    }

                    $response->throw();
                    $payload = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);
                    if (!$this->isValidSchedule($payload)) {
                        throw new UnexpectedValueException('VG TV-guide mangler forventede programdata.');
                    }
                    $results[$key] = ['schedule' => $payload, 'exception' => null];
                } catch (JsonException $exception) {
                    $results[$key] = ['schedule' => null, 'exception' => new UnexpectedValueException('VG TV-guide returnerte ugyldig JSON.', 0, $exception)];
                } catch (Throwable $exception) {
                    $results[$key] = ['schedule' => null, 'exception' => $exception];
                }
            }

            if ($retry !== [] && $attempt < 2) {
                usleep(250000);
            }
            $pending = $retry;
        }

        return $results;
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

        return self::WEEKLY_FOOTBALL_TITLES[(string) data_get($listing, 'title.title', '')] ?? null;
    }

    private function isWeeklyLiveFixture(array $listing): bool
    {
        $eventName = trim((string) data_get($listing, 'sportsEvent.name', ''));

        return ($listing['isLive'] ?? false) === true
            && ($listing['isRerun'] ?? false) !== true
            && $this->looksLikeFixtureName($eventName)
            && preg_match('/\b(studio|magasin|høydepunkter|highlights|oppsummering)\b/ui', $eventName) !== 1;
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
        ];
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
