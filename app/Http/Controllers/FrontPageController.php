<?php

namespace App\Http\Controllers;

use App\Services\ChangeHistoryService;
use App\Services\FlagDayService;
use App\Services\FrontPageThemeResolver;
use App\Services\NamedayService;
use App\Services\RingbladNewsService;
use App\Services\CorrectionalNewsService;
use Illuminate\Support\Carbon;

class FrontPageController extends Controller
{
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
            'flagDayOverview' => $flagDays->overview(),
            'localNews' => array_slice($ringbladNews->latest(), 0, 3),
            'correctionalNews' => $correctionalNews->frontPage(),
            'isThemePreview' => $isThemePreview,
            'theme' => $theme,
            'todayNamedays' => $namedays->forDate($today)['names'] ?? [],
        ]);
    }
}
