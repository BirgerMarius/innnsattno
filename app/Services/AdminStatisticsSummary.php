<?php

namespace App\Services;

use Illuminate\Support\Carbon;

class AdminStatisticsSummary
{
    public function read(): ?array
    {
        $path = config('admin.statistics_summary_path');

        if (! is_string($path) || ! is_file($path) || ! is_readable($path)) {
            return null;
        }

        try {
            $contents = file_get_contents($path);
            $data = json_decode($contents, true, 32, JSON_THROW_ON_ERROR);
        } catch (\Throwable $exception) {
            return null;
        }

        if (! is_array($data) || ! in_array($data['schema_version'] ?? null, [1, 2, 3, 4], true)) {
            return null;
        }

        if (in_array($data['schema_version'], [3, 4], true)) {
            if (! $this->validTimestamp($data['generated_at'] ?? null) || ! is_bool($data['test_data'] ?? null)) {
                return null;
            }
            $currentMethodology = $data['schema_version'] === 4;
            $periods = $this->validHumanPeriods($data['periods'] ?? null, $currentMethodology);
            if ($periods === null) {
                return null;
            }
            $topPages = $this->validTopPages($data['top_pages'] ?? null);
            if (($data['top_pages'] ?? null) !== null && $topPages === null) {
                return null;
            }
            $daily = $this->validDailyHumanStatistics($data['daily'] ?? null, $currentMethodology);
            if (($data['daily'] ?? null) !== null && $daily === null) {
                return null;
            }

            return [
                'human_traffic' => true,
                'current_methodology' => $currentMethodology,
                'generated_at' => Carbon::parse($data['generated_at']),
                'test_data' => $data['test_data'],
                'periods' => $periods,
                'top_pages' => $topPages,
                'daily' => $daily,
            ];
        }

        $latest = $data['latest_day'] ?? null;
        $week = $data['last_7_days'] ?? null;
        $topPage = $week['top_page'] ?? null;

        if (! $this->validDate($latest['date'] ?? null)
            || ! $this->validDate($week['from'] ?? null)
            || ! $this->validDate($week['to'] ?? null)
            || ! $this->validTimestamp($data['generated_at'] ?? null)
            || ! $this->validCount($latest['pageviews'] ?? null)
            || ! $this->validCount($latest['unique_visitors'] ?? null)
            || ! $this->validCount($week['pageviews'] ?? null)
            || ! $this->validCount($week['requests'] ?? null)
            || ! is_array($topPage)
            || ! is_string($topPage['path'] ?? null)
            || trim($topPage['path']) === ''
            || mb_strlen($topPage['path']) > 2048
            || $this->containsIpAddress($topPage['path'])
            || ! $this->validCount($topPage['pageviews'] ?? null)
            || ! is_bool($data['test_data'] ?? null)) {
            return null;
        }

        $summary = [
            'human_traffic' => false,
            'generated_at' => Carbon::parse($data['generated_at']),
            'test_data' => $data['test_data'],
            'latest_day' => [
                'date' => Carbon::createFromFormat('!Y-m-d', $latest['date']),
                'pageviews' => $latest['pageviews'],
                'unique_visitors' => $latest['unique_visitors'],
            ],
            'last_7_days' => [
                'from' => Carbon::createFromFormat('!Y-m-d', $week['from']),
                'to' => Carbon::createFromFormat('!Y-m-d', $week['to']),
                'pageviews' => $week['pageviews'],
                'requests' => $week['requests'],
                'top_page' => [
                    'path' => $topPage['path'],
                    'pageviews' => $topPage['pageviews'],
                ],
            ],
        ];

        $summary['top_pages'] = $this->validTopPages($data['top_pages'] ?? null);

        return $summary;
    }

    private function validCount($value): bool
    {
        return is_int($value) && $value >= 0;
    }

    private function validDate($value): bool
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return false;
        }

        try {
            return Carbon::createFromFormat('!Y-m-d', $value)->format('Y-m-d') === $value;
        } catch (\Throwable $exception) {
            return false;
        }
    }

    private function validTimestamp($value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        try {
            Carbon::parse($value);
            return true;
        } catch (\Throwable $exception) {
            return false;
        }
    }

    private function containsIpAddress(string $value): bool
    {
        foreach (preg_split('/[^0-9a-fA-F:.]+/', $value) as $part) {
            $candidate = trim($part, '[]');
            if ($candidate !== '' && filter_var($candidate, FILTER_VALIDATE_IP)) {
                return true;
            }
        }

        return false;
    }

    private function validTopPages($periods): ?array
    {
        if ($periods === null) {
            return null;
        }
        if (! is_array($periods)) {
            return null;
        }
        $validated = [];
        foreach (['1', '7', '30'] as $days) {
            $period = $periods[$days] ?? null;
            if (! is_array($period) || ! $this->validDate($period['from'] ?? null)
                || ! $this->validDate($period['to'] ?? null) || ! is_array($period['pages'] ?? null)) {
                return null;
            }
            $pages = [];
            $previousPage = null;
            foreach ($period['pages'] as $page) {
                if (! is_array($page) || ! is_string($page['name'] ?? null) || trim($page['name']) === ''
                    || mb_strlen($page['name']) > 120 || ! is_string($page['path'] ?? null)
                    || ! preg_match('#^/[A-Za-z0-9/_~.%+\-]*$#', $page['path']) || str_starts_with($page['path'], '//')
                    || mb_strlen($page['path']) > 2048
                    || $this->containsIpAddress($page['name']) || $this->containsIpAddress($page['path'])
                    || ! $this->validCount($page['pageviews'] ?? null)
                    || ! $this->validCount($page['unique_visitors'] ?? null)
                    || ($previousPage !== null && ! $this->correctlySortedAfter($page, $previousPage))) {
                    return null;
                }
                $previousPage = $page;
                $pages[] = $page;
            }
            $validated[$days] = ['from' => Carbon::createFromFormat('!Y-m-d', $period['from']),
                'to' => Carbon::createFromFormat('!Y-m-d', $period['to']), 'pages' => $pages];
        }
        return $validated;
    }

    private function validHumanPeriods($periods, bool $currentMethodology = false): ?array
    {
        if (! is_array($periods)) {
            return null;
        }
        $validated = [];
        foreach (['1', '7', '30'] as $days) {
            $period = $periods[$days] ?? null;
            $validatedPeriod = $this->validHumanPeriod($period, $currentMethodology);
            if ($validatedPeriod === null) {
                return null;
            }
            $validated[$days] = $validatedPeriod;
        }
        return $validated;
    }

    private function validHumanPeriod($period, bool $currentMethodology = false): ?array
    {
        $quality = is_array($period) ? ($period['traffic_quality'] ?? null) : null;
        if (! is_array($period) || ! is_array($quality)
            || ! $this->validDate($period['from'] ?? null) || ! $this->validDate($period['to'] ?? null)
            || ! $this->validCount($period['suspected_human_pageviews'] ?? null)
            || ! $this->validCount($period['suspected_visitors'] ?? null)
            || ! $this->validCount($period['sessions'] ?? null)
            || ! $this->validCount($period['print_pageviews'] ?? null)) {
            return null;
        }
        foreach (['raw_requests', 'known_automated_technical_requests', 'known_bot', 'monitoring', 'scanner', 'other', 'excluded', 'single_page_candidates'] as $key) {
            if (! $this->validCount($quality[$key] ?? null)) {
                return null;
            }
        }
        if ($quality['known_automated_technical_requests'] !== $quality['known_bot'] + $quality['monitoring'] + $quality['scanner'] + $quality['excluded']
            || $quality['known_automated_technical_requests'] > $quality['raw_requests']
            || $quality['other'] > $quality['raw_requests']
            || $quality['single_page_candidates'] > $quality['other']
            || $period['suspected_human_pageviews'] > $quality['raw_requests']) {
            return null;
        }
        $coverage = $currentMethodology ? $this->validCoverage($period['coverage'] ?? null, $period['from'], $period['to']) : null;
        $features = $currentMethodology ? $this->validFeatures($period['features'] ?? null) : null;
        $comparison = $currentMethodology ? $this->validComparison($period['comparison'] ?? null) : null;
        $featurePrints = is_array($features) ? array_sum(array_column($features, 'print_pageviews')) : null;
        if ($currentMethodology && ($coverage === null || $features === null || $featurePrints !== $period['print_pageviews']
            || max([0, ...array_column($features ?? [], 'unique_networks')]) > $period['suspected_visitors']
            || ($period['comparison'] ?? null) !== null && $comparison === null)) {
            return null;
        }
        return [
            'from' => Carbon::createFromFormat('!Y-m-d', $period['from']),
            'to' => Carbon::createFromFormat('!Y-m-d', $period['to']),
            'suspected_human_pageviews' => $period['suspected_human_pageviews'],
            'suspected_visitors' => $period['suspected_visitors'], 'sessions' => $period['sessions'],
            'print_pageviews' => $period['print_pageviews'], 'traffic_quality' => $quality,
            'coverage' => $coverage, 'features' => $features, 'comparison' => $comparison,
        ];
    }

    private function validDailyHumanStatistics($daily, bool $currentMethodology = false): ?array
    {
        if ($daily === null) {
            return null;
        }
        if (! is_array($daily)) {
            return null;
        }
        $validated = [];
        foreach ($daily as $date => $entry) {
            if (! is_string($date) || ! $this->validDate($date) || ! is_array($entry)) {
                return null;
            }
            $period = $this->validHumanPeriod($entry, $currentMethodology);
            $rankings = $this->validTopPages([
                '1' => ['from' => $date, 'to' => $date, 'pages' => $entry['pages'] ?? null],
                '7' => ['from' => $date, 'to' => $date, 'pages' => $entry['pages'] ?? null],
                '30' => ['from' => $date, 'to' => $date, 'pages' => $entry['pages'] ?? null],
            ]);
            if ($period === null || $rankings === null || ! $period['from']->isSameDay($period['to']) || ! $period['from']->isSameDay(Carbon::createFromFormat('!Y-m-d', $date))) {
                return null;
            }
            $period['pages'] = $rankings['1']['pages'];
            $validated[$date] = $period;
        }
        return $validated;
    }

    private function validCoverage($coverage, string $from, string $to): ?array
    {
        if (! is_array($coverage) || ! is_array($coverage['available_dates'] ?? null)
            || ! $this->validCount($coverage['covered_days'] ?? null) || ! $this->validCount($coverage['expected_days'] ?? null)
            || ! is_bool($coverage['complete'] ?? null) || ! is_array($coverage['classifier_versions'] ?? null)) {
            return null;
        }
        $expected = Carbon::createFromFormat('!Y-m-d', $from)->diffInDays(Carbon::createFromFormat('!Y-m-d', $to)) + 1;
        if ($coverage['expected_days'] !== $expected || $coverage['covered_days'] !== count($coverage['available_dates'])
            || $coverage['complete'] !== ($coverage['covered_days'] === $expected)) {
            return null;
        }
        $previous = null;
        foreach ($coverage['available_dates'] as $date) {
            if (! $this->validDate($date) || $date < $from || $date > $to || ($previous !== null && $date <= $previous)) {
                return null;
            }
            $previous = $date;
        }
        foreach ($coverage['classifier_versions'] as $version) {
            if (! is_int($version) || $version !== 4) {
                return null;
            }
        }
        return $coverage;
    }

    private function validFeatures($features): ?array
    {
        if (! is_array($features)) {
            return null;
        }
        $previous = null;
        foreach ($features as $feature) {
            if (! is_array($feature) || ! is_string($feature['name'] ?? null) || trim($feature['name']) === ''
                || mb_strlen($feature['name']) > 120 || ! $this->validCount($feature['pageviews'] ?? null)
                || ! $this->validCount($feature['unique_networks'] ?? null) || ! $this->validCount($feature['print_pageviews'] ?? null)
                || $feature['print_pageviews'] > $feature['pageviews']
                || ($previous !== null && ($feature['pageviews'] > $previous['pageviews']
                    || ($feature['pageviews'] === $previous['pageviews'] && strcmp($feature['name'], $previous['name']) < 0)))) {
                return null;
            }
            $previous = $feature;
        }
        return $features;
    }

    private function validComparison($comparison): ?array
    {
        if ($comparison === null) {
            return null;
        }
        if (! is_array($comparison) || ! $this->validDate($comparison['from'] ?? null)
            || ! $this->validDate($comparison['to'] ?? null) || ! $this->validCount($comparison['pageviews'] ?? null)
            || ! $this->validCount($comparison['sessions'] ?? null)) {
            return null;
        }
        return ['from' => Carbon::createFromFormat('!Y-m-d', $comparison['from']),
            'to' => Carbon::createFromFormat('!Y-m-d', $comparison['to']),
            'pageviews' => $comparison['pageviews'], 'sessions' => $comparison['sessions']];
    }

    private function correctlySortedAfter(array $page, array $previous): bool
    {
        if ($page['pageviews'] !== $previous['pageviews']) {
            return $page['pageviews'] < $previous['pageviews'];
        }
        if ($page['unique_visitors'] !== $previous['unique_visitors']) {
            return $page['unique_visitors'] < $previous['unique_visitors'];
        }
        return strcmp($page['path'], $previous['path']) >= 0;
    }
}
