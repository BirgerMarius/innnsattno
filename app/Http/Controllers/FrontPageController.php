<?php

namespace App\Http\Controllers;

use App\Services\ChangeHistoryService;
use App\Services\FlagDayService;
use App\Services\FrontPageThemeResolver;
use App\Services\NamedayService;
use App\Services\RingbladNewsService;
use App\Services\CorrectionalNewsService;
use App\Services\TimedVisibilityService;
use Illuminate\Support\Carbon;

class FrontPageController extends Controller
{
    public function __construct(private TimedVisibilityService $timedVisibility)
    {
    }

    public function index(RingbladNewsService $ringbladNews, CorrectionalNewsService $correctionalNews, FlagDayService $flagDays, NamedayService $namedays, ChangeHistoryService $changeHistory, FrontPageThemeResolver $themeResolver)
    {
        return $this->frontPage(
            $ringbladNews,
            $correctionalNews,
            $flagDays,
            $namedays,
            $changeHistory,
            $themeResolver->resolve()
        );
    }

    public function preview(string $theme, RingbladNewsService $ringbladNews, CorrectionalNewsService $correctionalNews, FlagDayService $flagDays, NamedayService $namedays, ChangeHistoryService $changeHistory)
    {
        $themes = config('front_page_themes.themes', []);

        abort_unless(isset($themes[$theme]), 404);

        return $this->frontPage($ringbladNews, $correctionalNews, $flagDays, $namedays, $changeHistory, $themes[$theme], true);
    }

    public function overview()
    {
        return view('design-test.index', ['themes' => config('front_page_themes.themes', [])]);
    }

    private function frontPage(RingbladNewsService $ringbladNews, CorrectionalNewsService $correctionalNews, FlagDayService $flagDays, NamedayService $namedays, ChangeHistoryService $changeHistory, ?array $theme = null, bool $isThemePreview = false)
    {
        $today = Carbon::now(FlagDayService::TIMEZONE);

        return view('tv.guide', [
            'changeHistory' => $changeHistory->get(),
            'clockChangeReminder' => app(\App\Services\ClockChangeReminderService::class)->forDate($today),
            'flagDayOverview' => $flagDays->overview(),
            'localNews' => array_slice($ringbladNews->latest(), 0, 3),
            'correctionalNews' => $correctionalNews->frontPage(),
            'isThemePreview' => $isThemePreview,
            'theme' => $theme,
            'todayNamedays' => $namedays->forDate($today)['names'] ?? [],
            'showNationsLeagueNewBadge' => $this->timedVisibility->isVisible(
                config('nations_league_badge.starts_at'),
                (int) config('nations_league_badge.duration_days', 7),
                $today,
            ),
            'showActivitiesNewBadge' => $this->showActivitiesNewBadge($today),
            'showFangenyttNewBadge' => $this->showNewBadge($today, config('fangenytt.published_at')),
            'showWeeklyFootballTvNewBadge' => $this->showNewBadge($today, config('weekly_football_tv.published_at')),
            'showPodcastRecommendation' => $this->showPodcastRecommendation($today),
        ]);
    }

    private function showActivitiesNewBadge(Carbon $now): bool
    {
        return $this->showNewBadge($now, config('activities.published_at'));
    }

    private function showNewBadge(Carbon $now, mixed $publishedAt): bool
    {
        return $this->timedVisibility->isVisible($publishedAt, 14, $now);
    }

    private function showPodcastRecommendation(Carbon $now): bool
    {
        $publishedAt = config('podcast_recommendation.published_at');

        return $this->timedVisibility->isVisible($publishedAt, 28, $now);
    }
}
