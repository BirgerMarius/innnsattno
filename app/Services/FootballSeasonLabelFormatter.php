<?php

namespace App\Services;

class FootballSeasonLabelFormatter
{
    /**
     * Return the year part of SportsNext's season metadata for use after the
     * established, local competition name. Metadata without a year is kept as
     * is so the existing Sesong-ID fallback remains visible.
     */
    public function season(?string $seasonName): ?string
    {
        if ($seasonName === null || trim($seasonName) === '') {
            return $seasonName;
        }

        if (preg_match('/\b(20\d{2}(?:\s*[\/–-]\s*(?:20)?\d{2})?)\b/u', $seasonName, $matches)) {
            return $matches[1];
        }

        return $seasonName;
    }
}
