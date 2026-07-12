<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;

class MonthCalendarController extends Controller
{
    private const PERIOD_CURRENT_MONTH = 'current_month';
    private const PERIOD_REST_OF_YEAR = 'rest_of_year';

    public function index()
    {
        return view('calendar.index', [
            'defaultPeriod' => self::PERIOD_CURRENT_MONTH,
        ]);
    }

    public function print(Request $request)
    {
        $validated = $request->validate([
            'periode' => 'required|in:' . self::PERIOD_CURRENT_MONTH . ',' . self::PERIOD_REST_OF_YEAR,
        ]);

        $today = now(config('app.timezone'))->startOfDay();
        $endMonth = $validated['periode'] === self::PERIOD_REST_OF_YEAR
            ? $today->copy()->month(12)->startOfMonth()
            : $today->copy()->startOfMonth();

        $months = [];
        $month = $today->copy()->startOfMonth();

        while ($month->lessThanOrEqualTo($endMonth)) {
            $months[] = $this->buildMonth($month);
            $month->addMonthNoOverflow();
        }

        return view('calendar.print', [
            'months' => $months,
        ]);
    }

    private function buildMonth(Carbon $month): array
    {
        $firstDay = $month->copy()->startOfMonth();
        $lastDay = $month->copy()->endOfMonth();
        $cursor = $firstDay->copy()->startOfWeek(Carbon::MONDAY);
        $lastGridDay = $lastDay->copy()->endOfWeek(Carbon::SUNDAY);
        $holidays = $this->norwegianHolidays((int) $month->year);
        $weeks = [];

        while ($cursor->lessThanOrEqualTo($lastGridDay)) {
            $weekStart = $cursor->copy();
            $days = [];

            for ($i = 0; $i < 7; $i++) {
                $day = $cursor->copy();

                $days[] = [
                    'number' => $day->isSameMonth($month) ? $day->day : null,
                    'isWeekend' => $day->isWeekend(),
                    'holidayName' => $day->isSameMonth($month) ? ($holidays[$day->toDateString()] ?? null) : null,
                ];

                $cursor->addDay();
            }

            $weeks[] = [
                'number' => $weekStart->isoWeek(),
                'days' => $days,
            ];
        }

        return [
            'title' => ucfirst($month->copy()->locale('nb')->translatedFormat('F Y')),
            'weeks' => $weeks,
        ];
    }

    private function norwegianHolidays(int $year): array
    {
        $easterSunday = Carbon::create($year, 3, 21, 0, 0, 0, config('app.timezone'))
            ->addDays(easter_days($year));

        $holidays = [
            Carbon::create($year, 1, 1, 0, 0, 0, config('app.timezone'))->toDateString() => '1. nyttårsdag',
            Carbon::create($year, 5, 1, 0, 0, 0, config('app.timezone'))->toDateString() => 'Arbeidernes dag',
            Carbon::create($year, 5, 17, 0, 0, 0, config('app.timezone'))->toDateString() => 'Grunnlovsdag',
            Carbon::create($year, 12, 25, 0, 0, 0, config('app.timezone'))->toDateString() => '1. juledag',
            Carbon::create($year, 12, 26, 0, 0, 0, config('app.timezone'))->toDateString() => '2. juledag',
        ];

        $movingHolidays = [
            -3 => 'Skjærtorsdag',
            -2 => 'Langfredag',
            0 => '1. påskedag',
            1 => '2. påskedag',
            39 => 'Kristi himmelfartsdag',
            49 => '1. pinsedag',
            50 => '2. pinsedag',
        ];

        foreach ($movingHolidays as $daysFromEaster => $name) {
            $holidays[$easterSunday->copy()->addDays($daysFromEaster)->toDateString()] = $name;
        }

        return $holidays;
    }
}
