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

    private function prisons(): array
    {
        return collect(config('prisons.all'))->map(fn (array $prison, string $slug) => [
            'id' => $prison['prayer_location_id'], 'name' => $prison['name'], 'slug' => $slug,
        ])->all();
    }

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

    public function prison(string $prison, Request $request)
    {
        return $this->showMonth($prison, $request);
    }

    public function prisonPrint(string $prison, Request $request)
    {
        return $this->showMonth($prison, $request, true);
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

        $location = $this->prisons()[$prison];
        if (! is_int($location['id']) && ! ctype_digit((string) $location['id'])) {
            return view($print ? 'prayer.print' : 'prayer.index', [
                'days' => [], 'prison' => $location, 'year' => $year, 'month' => $month,
                'monthName' => $monthNames[$month],
                'error' => 'Bønnetider for Kongsvinger er under klargjøring. Leverandørens steds-ID må bekreftes før tjenesten kan åpnes.',
                'returnUrl' => $print ? $this->returnUrl($request) : null,
            ]);
        }

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
            // The page only offers navigation and printing for the following
            // month. Bønnetid.no does not publish dependable data a year in
            // advance, so accepting longer ranges merely lets crawlers cause
            // unnecessary API calls and false operational alerts.
            $currentMonth->copy()->addMonth(),
        );
    }

    private function currentMonthUrl(string $prison, Carbon $currentMonth): string
    {
        $path = match ($prison) {
            'ringerike' => '/bonnetider', 'ilseng' => '/bonnetider-ilseng', default => '/'.$prison.'/bonnetider',
        };

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
