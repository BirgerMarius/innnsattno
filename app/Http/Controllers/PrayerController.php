<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PrayerController extends Controller
{
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
        $year = $request->get('year', now()->year);
        $month = $request->get('month', now()->month);

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

        $response = Http::acceptJson()
            ->withHeaders([
                'api-token' => '92affaa6-0e9b-4402-8d8a-0fcd8d9e91ec'
            ])
            ->get(
                "https://api.bonnetid.no/prayertimes/{$location['id']}/{$year}/{$month}/"
            );

        $days = json_decode($response, true);

        $view = $print ? 'prayer.print' : 'prayer.index';

return view($view, [
    'days' => $days,
    'prison' => $location,
    'year' => $year,
    'month' => $month,
    'monthName' => $monthNames[$month],
]);
    }
}