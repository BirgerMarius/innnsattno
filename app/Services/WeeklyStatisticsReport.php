<?php

namespace App\Services;

use Illuminate\Support\Carbon;

class WeeklyStatisticsReport
{
    public function __construct(private AdminStatisticsSummary $summary)
    {
    }

    /** The key is the local Monday starting the reported calendar week. */
    public function forWeek(Carbon $weekStart): ?array
    {
        $weekStart = $weekStart->copy()->timezone('Europe/Oslo')->startOfDay();
        $data = $this->summary->read();
        if (! $data || ! $data['current_methodology']) {
            return null;
        }
        $week = $data['calendar_weeks'][$weekStart->toDateString()] ?? null;
        if (! $week) {
            return null;
        }
        $end = $weekStart->copy()->addWeek();
        $generated = $data['generated_at']->copy()->timezone('Europe/Oslo');
        $complete = $week['coverage']['complete'] && $generated->greaterThanOrEqualTo($end);
        $previous = $week['comparison'];
        $comparable = $previous !== null;
        return [
            'week_start' => $weekStart,
            'week_end_exclusive' => $end,
            'week_number' => (int) $weekStart->isoWeek(),
            'subject' => sprintf('Innsatt.no – ukesrapport uke %02d, %s–%s', (int) $weekStart->isoWeek(), $weekStart->format('d.m.'), $weekStart->copy()->addDays(6)->format('d.m.Y')),
            'ready' => $complete,
            'generated_at' => $generated,
            'coverage' => $week['coverage'],
            'classifier_versions' => $week['coverage']['classifier_versions'],
            'metrics' => [
                'front_pageviews' => collect($week['pages'])->firstWhere('path', '/tv')['pageviews'] ?? 0,
                'pageviews' => $week['suspected_human_pageviews'],
                'sessions' => $week['sessions'],
                'networks' => $week['suspected_visitors'],
                'print_opens' => $week['print_pageviews'],
            ],
            'quality' => $week['traffic_quality'],
            'days' => $this->days($data['daily'], $weekStart),
            'pages' => $week['pages'],
            'features' => $week['features'],
            'prints_by_prison' => $week['prints_by_prison'],
            'comparison' => $comparable ? $previous : null,
            'comparison_note' => $comparable ? null : 'Ikke vist: ufullstendig loggdekning eller ulike klassifiseringsregler.',
        ];
    }

    private function days(array $daily, Carbon $start): array
    {
        $result = [];
        for ($offset = 0; $offset < 7; $offset++) {
            $day = $start->copy()->addDays($offset);
            $entry = $daily[$day->toDateString()] ?? null;
            $result[] = ['date' => $day, 'pageviews' => $entry['suspected_human_pageviews'] ?? null,
                'sessions' => $entry['sessions'] ?? null];
        }
        return $result;
    }
}
