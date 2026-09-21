<?php

namespace App\Http\Controllers;

use App\Services\DwScheduleService;
use App\Services\TvGuideService;
use Illuminate\Support\Carbon;

class TvPrintController extends Controller
{
    public function ringerike(TvGuideService $tvGuideService, DwScheduleService $dwScheduleService)
    {
        return $this->render('ringerike', now('Europe/Oslo'), false, $tvGuideService, $dwScheduleService);
    }

    public function ilseng(TvGuideService $tvGuideService, DwScheduleService $dwScheduleService)
    {
        return $this->render('ilseng', now('Europe/Oslo'), false, $tvGuideService, $dwScheduleService);
    }

    public function adminPrint(string $prison, Carbon $date, TvGuideService $tvGuideService, DwScheduleService $dwScheduleService)
    {
        return $this->render($prison, $date, true, $tvGuideService, $dwScheduleService);
    }

    private function render(string $prison, Carbon $date, bool $isSelectedDate, TvGuideService $tvGuideService, DwScheduleService $dwScheduleService)
    {
        $date = $date->copy()->setTimezone('Europe/Oslo')->startOfDay();
        $channels = $prison === 'ringerike'
            ? TvGuideService::ringerikeChannels()
            : TvGuideService::ilsengChannels();

        $tvChannels = $tvGuideService->withDisplayTitles(
            $tvGuideService->getSchedule($date, $channels, $prison.'-print'),
        );

        if ($prison === 'ilseng') {
            return view('pdf-ilseng', [
                'channels' => $tvChannels,
                'printDate' => $date,
                'isSelectedDate' => $isSelectedDate,
            ]);
        }

        $dwChannel = $dwScheduleService->channelForDate($date);

        if ($dwChannel !== null) {
            $dwInsertIndex = null;

            foreach ($tvChannels as $index => $tvChannel) {
                if (($tvChannel['channel']['slug'] ?? null) === 'bbc-world-news') {
                    $dwInsertIndex = $index + 1;
                    break;
                }
            }

            if ($dwInsertIndex === null) {
                foreach ($tvChannels as $index => $tvChannel) {
                    if (($tvChannel['channel']['slug'] ?? null) === 'al-jazeera-english') {
                        $dwInsertIndex = $index;
                        break;
                    }
                }
            }

            array_splice($tvChannels, $dwInsertIndex ?? count($tvChannels), 0, [$dwChannel]);
        }

        return view('pdf', [
            'channels' => $tvChannels,
            'printDate' => $date,
            'isSelectedDate' => $isSelectedDate,
        ]);
    }
}
