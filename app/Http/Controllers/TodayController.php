<?php

namespace App\Http\Controllers;

use App\Services\FlagDayService;
use App\Services\NamedayService;
use App\Services\TodayContentService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class TodayController extends Controller
{
    public function show(
        Request $request,
        NamedayService $namedays,
        TodayContentService $todayContent,
        FlagDayService $flagDays,
        ?string $date = null
    ) {
        $selectedDate = $date
            ? CarbonImmutable::createFromFormat('!Y-m-d', $date, FlagDayService::TIMEZONE)
            : CarbonImmutable::now(FlagDayService::TIMEZONE)->startOfDay();

        if (!$selectedDate || ($date && $selectedDate->format('Y-m-d') !== $date)) {
            abort(404);
        }

        $flagDay = collect($flagDays->forYear($selectedDate->year))
            ->first(fn (array $item) => $item['date']->isSameDay($selectedDate));

        return view('today.show', [
            'date' => $selectedDate,
            'today' => CarbonImmutable::now(FlagDayService::TIMEZONE)->startOfDay(),
            'namedays' => $namedays->forDate($selectedDate)['names'] ?? [],
            'history' => $todayContent->forDate($selectedDate),
            'flagDay' => $flagDay,
        ]);
    }
}
