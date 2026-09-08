<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Services\PrayerTimeService;

class PrayerController extends Controller
{
    public function __construct(private PrayerTimeService $prayerTimes)
    {
    }

    private array $prisons = [
        'ringerike' => [
            'id' => 146,
            'name' => 'Ringerike fengsel',
        ],
        'ilseng' => [
            'id' => 181,
            'name' => 'Ilseng fengsel',
        ],
    ];

    public function ringerike(Request $request)
    {
        return $this->showMonth('ringerike', $request);
    }

    public function ilseng(Request $request)
    {
        return $this->showMonth('ilseng', $request);
    }
    public function printRingerike(Request $request)
    {
        return $this->showMonth('ringerike', $request, true);
    }

    public function printIlseng(Request $request)
    {
        return $this->showMonth('ilseng', $request, true);
    }

    private function showMonth(string $prison, Request $request, bool $print = false)
    {
        $currentMonth = now('Europe/Oslo')->startOfMonth();
        $year = $this->integerQueryValue($request->query('year', $currentMonth->year), 1000, 9999);
        $month = $this->integerQueryValue($request->query('month', $currentMonth->month), 1, 12);

        if ($year === null || $month === null || ! $this->isAllowedMonth($year, $month, $currentMonth)) {
            return redirect()->to($this->currentMonthUrl($prison, $currentMonth));
        }

        $monthNames = [
            1 => 'Januar',
            2 => 'Februar',
            3 => 'Mars',
            4 => 'April',
            5 => 'Mai',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'August',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        $location = $this->prisons[$prison];

        $days = $this->prayerTimes->getMonth(
            $location['id'],
            (int) $year,
            (int) $month,
            'prayer-times'
        );

        $view = $print ? 'prayer.print' : 'prayer.index';

        return view($view, [
            'days' => $days,
            'prison' => $location,
            'year' => $year,
            'month' => $month,
            'monthName' => $monthNames[$month],
            'error' => $days === [] ? 'Bønnetider kunne ikke hentes akkurat nå. Prøv igjen senere.' : null,
            'returnUrl' => $print ? $this->returnUrl($request) : null,
        ]);
    }

    private function integerQueryValue(mixed $value, int $minimum, int $maximum): ?int
    {
        if ((! is_string($value) && ! is_int($value)) || ! preg_match('/^\d+$/D', (string) $value)) {
            return null;
        }

        $integer = (int) $value;

        return $integer >= $minimum && $integer <= $maximum ? $integer : null;
    }

    private function isAllowedMonth(int $year, int $month, Carbon $currentMonth): bool
    {
        $requestedMonth = Carbon::create($year, $month, 1, 0, 0, 0, 'Europe/Oslo')->startOfMonth();

        return $requestedMonth->betweenIncluded(
            $currentMonth->copy()->subMonth(),
            $currentMonth->copy()->addMonths(12),
        );
    }

    private function currentMonthUrl(string $prison, Carbon $currentMonth): string
    {
        $path = $prison === 'ringerike' ? '/bonnetider' : '/bonnetider-ilseng';

        return $path.'?'.http_build_query([
            'year' => $currentMonth->year,
            'month' => $currentMonth->month,
        ]);
    }

    private function returnUrl(Request $request): string
    {
        $path = preg_replace('#/utskrift$#', '', $request->path());
        $query = $request->getQueryString();

        return '/'.ltrim($path, '/').($query ? '?'.$query : '');
    }
}
