<?php

namespace App\Http\Controllers;

use App\Services\FlagDayService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PrisonCalendarController extends Controller
{
    public function index(Request $request, string $prison, FlagDayService $flags, MonthCalendarController $calendar)
    {
        $today = CarbonImmutable::now('Europe/Oslo')->startOfDay();
        $data = $request->validate([
            'year' => 'sometimes|required|integer|min:1970|max:2099',
            'month' => 'sometimes|required|integer|between:1,12',
            'selected' => 'nullable|date_format:Y-m-d|after_or_equal:1970-01-01|before_or_equal:2099-12-31',
            'tool' => ['nullable', Rule::in(['distance', 'period', 'shift'])],
            'date' => 'required_if:tool,distance|nullable|date_format:Y-m-d|after_or_equal:1970-01-01|before_or_equal:2099-12-31',
            'from' => 'required_if:tool,period|nullable|date_format:Y-m-d|after_or_equal:1970-01-01|before_or_equal:2099-12-31',
            'to' => 'required_if:tool,period|nullable|date_format:Y-m-d|after_or_equal:1970-01-01|before_or_equal:2099-12-31',
            'weekday' => 'required_if:tool,period|nullable|integer|between:0,6',
            'start' => 'required_if:tool,shift|nullable|date_format:Y-m-d|after_or_equal:1970-01-01|before_or_equal:2099-12-31',
            'days' => 'required_if:tool,shift|nullable|integer|between:0,36500',
            'direction' => ['required_if:tool,shift', 'nullable', Rule::in(['before', 'after'])],
        ]);
        $month = CarbonImmutable::create((int) ($data['year'] ?? $today->year), (int) ($data['month'] ?? $today->month), 1, 0, 0, 0, 'Europe/Oslo');
        $first = $month->startOfWeek();
        $last = $month->endOfMonth()->endOfWeek();
        $holidays = [];
        $flagDays = [];
        foreach (range($first->year, $last->year) as $year) {
            $holidays += $calendar->norwegianHolidays($year);
            // Eldre flaggdagregler og kongelige fødselsdager var annerledes.
            if ($year >= 2026) {
                foreach ($flags->forYear($year) as $flag) {
                    $flagDays[$flag['date']->toDateString()] = $flag['name'];
                }
            }
            if ($year === 2026) {
                $flagDays['2026-02-21'] = 'Kong Harald Vs fødselsdag';
            }
        }
        $weeks = [];
        for ($day = $first; $day->lte($last); $day = $day->addWeek()) {
            $weeks[] = ['start' => $day, 'days' => array_map(fn ($i) => $day->addDays($i), range(0, 6))];
        }
        $nextHolidays = $calendar->norwegianHolidays($today->year) + $calendar->norwegianHolidays($today->year + 1);
        ksort($nextHolidays);
        $nextHoliday = null;
        foreach ($nextHolidays as $date => $name) {
            if ($date >= $today->toDateString()) { $nextHoliday = ['date' => $date, 'name' => $name]; break; }
        }
        $result = null;
        $error = null;
        $parse = fn ($value) => CarbonImmutable::createFromFormat('!Y-m-d', $value, 'Europe/Oslo');
        if (($data['tool'] ?? '') === 'distance') {
            $date = $parse($data['date']);
            $result = ['type' => 'distance', 'date' => $date, 'days' => $today->diffInDays($date), 'past' => $date->lt($today)];
        } elseif (($data['tool'] ?? '') === 'period') {
            $from = $parse($data['from']); $to = $parse($data['to']);
            if ($to->lt($from)) {
                $error = 'Sluttdatoen må være på eller etter startdatoen.';
            } else {
                $days = $from->diffInDays($to) + 1;
                $offset = ((int) $data['weekday'] - $from->dayOfWeek + 7) % 7;
                $result = ['type' => 'period', 'days' => $days, 'count' => $offset < $days ? intdiv($days - 1 - $offset, 7) + 1 : 0, 'weekday' => (int) $data['weekday']];
            }
        } elseif (($data['tool'] ?? '') === 'shift') {
            $date = $parse($data['start'])->addDays(($data['direction'] === 'before' ? -1 : 1) * (int) $data['days']);
            if ($date->year < 1970 || $date->year > 2099) {
                $error = 'Resultatet må være mellom år 1970 og 2099.';
            } else {
                $result = ['type' => 'shift', 'date' => $date];
            }
        }
        return view('calendar.prison', compact('prison', 'today', 'month', 'weeks', 'holidays', 'flagDays', 'nextHoliday', 'result', 'error', 'data') + ['nextFlag' => $flags->overview($today)['next']]);
    }
}
