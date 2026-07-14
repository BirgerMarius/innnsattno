<?php

namespace App\Services\Schibsted;

class JsonStructureInspector
{
    private const DEFAULT_LIST_EXAMPLE_LIMIT = 5;
    private const DEFAULT_ID_EXAMPLE_LIMIT = 10;

    private const SIGNAL_KEYS = [
        'events',
        'standings',
        'participants',
        'teams',
        'players',
        'lineups',
        'statistics',
        'assets',
        'tournament',
        'season',
    ];

    private const SENSITIVE_KEYS = ['email', 'mail', 'authorEmail', 'createdByEmail'];

    public function inspect($data, int $maxDepth = 2): array
    {
        return [
            'top_level_type' => $this->typeOf($data),
            'top_level_keys' => is_array($data) && $this->isAssoc($data) ? array_keys($data) : [],
            'list_counts' => $this->listCounts($data),
            'first_item_fields' => $this->firstItemFields($data),
            'structure' => $this->structure($data, 0, $maxDepth),
            'id_fields' => $this->idFields($data),
            'signals' => $this->signals($data),
        ];
    }

    private function listCounts($data): array
    {
        $counts = [];
        $this->collectListCounts($data, $counts);

        ksort($counts);

        return $counts;
    }

    private function collectListCounts($data, array &$counts, string $prefix = '', int $depth = 0): void
    {
        if (!is_array($data) || $depth > 2) {
            return;
        }

        foreach ($data as $key => $value) {
            if (!is_array($value) || $this->isSensitiveKey((string) $key)) {
                continue;
            }

            $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;
            $displayPath = $this->displayPath($path);

            if (!$this->isAssoc($value)) {
                $counts[$displayPath] = $this->appendExample(
                    $counts[$displayPath] ?? $this->emptySummary(self::DEFAULT_LIST_EXAMPLE_LIMIT),
                    [
                        'path' => $path,
                        'count' => count($value),
                    ]
                );
            }

            $this->collectListCounts($value, $counts, $path, $depth + 1);
        }
    }

    private function firstItemFields($data): array
    {
        if (!is_array($data)) {
            return [];
        }

        $fields = [];

        foreach ($data as $key => $value) {
            if (!is_array($value) || $this->isAssoc($value) || $this->isSensitiveKey((string) $key)) {
                continue;
            }

            $first = $value[0] ?? null;

            if (is_array($first) && $this->isAssoc($first)) {
                $fields[(string) $key] = array_values(array_filter(array_keys($first), function ($field) {
                    return !$this->isSensitiveKey((string) $field);
                }));
            }
        }

        return $fields;
    }

    private function structure($data, int $depth, int $maxDepth)
    {
        if ($depth >= $maxDepth || !is_array($data)) {
            return $this->typeOf($data);
        }

        if (!$this->isAssoc($data)) {
            return [
                'type' => 'list',
                'count' => count($data),
                'item' => $this->structure($data[0] ?? null, $depth + 1, $maxDepth),
            ];
        }

        $out = ['type' => 'object', 'fields' => []];

        foreach (array_slice($data, 0, 30, true) as $key => $value) {
            if ($this->isSensitiveKey((string) $key)) {
                continue;
            }

            $out['fields'][$key] = $this->structure($value, $depth + 1, $maxDepth);
        }

        return $out;
    }

    private function idFields($data): array
    {
        $summary = $this->emptySummary(self::DEFAULT_ID_EXAMPLE_LIMIT);
        $seen = [];
        $this->collectIdFields($data, $summary, $seen);

        return $summary;
    }

    private function collectIdFields($data, array &$summary, array &$seen, string $prefix = '', int $depth = 0): void
    {
        if (!is_array($data) || $depth > 3) {
            return;
        }

        foreach ($data as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;
            $displayPath = $this->displayPath($path);

            if (preg_match('/(^id$|Id$|_id$)/', (string) $key) && is_scalar($value)) {
                if (!isset($seen[$displayPath])) {
                    $summary = $this->appendExample($summary, $displayPath);
                    $seen[$displayPath] = true;
                } else {
                    $summary['total']++;
                    $summary['hidden'] = max(0, $summary['total'] - count($summary['examples']));
                }
            }

            if (is_array($value)) {
                $this->collectIdFields($value, $summary, $seen, $path, $depth + 1);
            }
        }
    }

    private function signals($data): array
    {
        $signals = array_fill_keys(self::SIGNAL_KEYS, false);
        $this->markSignals($data, $signals);

        return $signals;
    }

    private function markSignals($data, array &$signals): void
    {
        if (!is_array($data)) {
            return;
        }

        foreach ($data as $key => $value) {
            foreach (self::SIGNAL_KEYS as $signal) {
                if (strtolower((string) $key) === strtolower($signal)) {
                    $signals[$signal] = true;
                }
            }

            if (is_array($value)) {
                $this->markSignals($value, $signals);
            }
        }
    }

    private function typeOf($value): string
    {
        if (is_array($value)) {
            return $this->isAssoc($value) ? 'object' : 'list';
        }

        if (is_null($value)) {
            return 'null';
        }

        return gettype($value);
    }

    private function isAssoc(array $array): bool
    {
        return array_keys($array) !== range(0, count($array) - 1);
    }

    private function isSensitiveKey(string $key): bool
    {
        return in_array($key, self::SENSITIVE_KEYS, true) || str_contains(strtolower($key), 'email');
    }

    private function emptySummary(int $limit): array
    {
        return [
            'total' => 0,
            'shown' => 0,
            'hidden' => 0,
            'limit' => $limit,
            'examples' => [],
        ];
    }

    private function appendExample(array $summary, $example): array
    {
        $summary['total']++;

        if (count($summary['examples']) < $summary['limit']) {
            $summary['examples'][] = $example;
            $summary['shown'] = count($summary['examples']);
        }

        $summary['hidden'] = max(0, $summary['total'] - $summary['shown']);

        return $summary;
    }

    private function displayPath(string $path): string
    {
        return preg_replace('/(^|\.)\d+(?=\.|$)/', '$1*', $path) ?: $path;
    }
}
