<?php

namespace App\Services;

use Illuminate\Http\Request;

class PrintReturnUrl
{
    public function fromRequest(Request $request): ?string
    {
        $url = $request->query('return_to');

        return is_string($url) && $this->isSafe($url) ? $url : null;
    }

    public function isSafe(string $url): bool
    {
        $parts = parse_url($url);

        if ($parts === false || isset($parts['scheme'], $parts['host'], $parts['user'], $parts['pass'])
            || ! str_starts_with($url, '/') || str_starts_with($url, '//')) {
            return false;
        }

        return ! preg_match('#(?:^|/)(?:[^/]*print|[^/]*utskrift|fasit)$#', $parts['path'] ?? '');
    }
}
