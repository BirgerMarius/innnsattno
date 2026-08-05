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

        if (! is_array($data) || ($data['schema_version'] ?? null) !== 1) {
            return null;
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

        return [
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
}
