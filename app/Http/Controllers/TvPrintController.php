<?php

namespace App\Http\Controllers;

use App\Services\DwScheduleService;
use App\Services\TvGuideService;
use App\Services\PrisonContext;
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

    public function prison(string $prison, TvGuideService $tvGuideService, DwScheduleService $dwScheduleService, PrisonContext $prisons)
    {
        $context = $prisons->prison($prison);
        if (! $context['tv_available']) {
            return view('tv.pending', ['prison' => $context]);
        }

        return $this->render($prison, now('Europe/Oslo'), false, $tvGuideService, $dwScheduleService);
    }

    public function adminPrint(string $prison, Carbon $date, TvGuideService $tvGuideService, DwScheduleService $dwScheduleService)
    {
        return $this->render($prison, $date, true, $tvGuideService, $dwScheduleService);
    }

    private function render(string $prison, Carbon $date, bool $isSelectedDate, TvGuideService $tvGuideService, DwScheduleService $dwScheduleService)
    {
        $date = $date->copy()->setTimezone('Europe/Oslo')->startOfDay();
        $channels = TvGuideService::channelsForPrison($prison);

        $tvChannels = $tvGuideService->withDisplayTitles(
            $tvGuideService->getSchedule($date, $channels, $prison.'-print'),
        );
        $tvAvailable = $tvChannels !== [];
        $tvLastUpdated = $tvGuideService->lastUpdated($date, $channels);

        if ($prison === 'ilseng') {
            return view('pdf-ilseng', [
                'channels' => $tvChannels,
                'printDate' => $date,
                'isSelectedDate' => $isSelectedDate,
                'tvAvailable' => $tvAvailable,
                'tvLastUpdated' => $tvLastUpdated,
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
            'tvAvailable' => $tvAvailable,
            'tvLastUpdated' => $tvLastUpdated,
        ]);
    }
}
