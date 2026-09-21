<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\TvPrintController;
use App\Services\DwScheduleService;
use App\Services\TvGuideService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class AdminTvPrintController extends Controller
{
    private const PRISONS = ['ringerike', 'ilseng'];

    /* Verified against VG's TV API 21 September 2026: data was available from
       eight days back through thirteen days ahead; immediately outside it the
       API returned an empty schedule. */
    private const PAST_DAYS = 8;
    private const FUTURE_DAYS = 13;

    public function __invoke(Request $request, TvPrintController $printController, TvGuideService $tvGuideService, DwScheduleService $dwScheduleService)
    {
        $today = now('Europe/Oslo')->startOfDay();
        $validated = $request->validate([
            'prison' => ['required', Rule::in(self::PRISONS)],
            'date' => [
                'required',
                'date_format:Y-m-d',
                function (string $attribute, mixed $value, Closure $fail) use ($today): void {
                    try {
                        $date = Carbon::createFromFormat('!Y-m-d', (string) $value, 'Europe/Oslo');
                    } catch (\Throwable $exception) {
                        $fail('Datoen er ugyldig.');
                        return;
                    }

                    if ($date->format('Y-m-d') !== $value) {
                        $fail('Datoen er ugyldig.');
                        return;
                    }

                    if ($date->lt($today->copy()->subDays(self::PAST_DAYS)) || $date->gt($today->copy()->addDays(self::FUTURE_DAYS))) {
                        $fail('Datoen må være innenfor tilgjengelig datovindu i TV-guiden.');
                    }
                },
            ],
        ]);

        return $printController->adminPrint(
            $validated['prison'],
            Carbon::createFromFormat('!Y-m-d', $validated['date'], 'Europe/Oslo'),
            $tvGuideService,
            $dwScheduleService,
        );
    }
}
