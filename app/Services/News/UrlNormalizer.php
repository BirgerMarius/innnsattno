<?php

namespace App\Services\News;

class UrlNormalizer
{
    private const TRACKING = ['fbclid','utm_source','utm_medium','utm_campaign','utm_content','utm_term'];

    public function normalize(string $url): ?string
    {
        $url = trim(html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if (! filter_var($url, FILTER_VALIDATE_URL) || ! in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http','https'], true)) return null;
        $parts = parse_url($url); if ($parts === false || empty($parts['host'])) return null;
        $query = []; parse_str($parts['query'] ?? '', $query);
        foreach (array_keys($query) as $key) if (in_array(strtolower($key), self::TRACKING, true)) unset($query[$key]);
        $normalized = strtolower($parts['scheme']).'://'.strtolower($parts['host']);
        if (isset($parts['port'])) $normalized .= ':'.$parts['port'];
        $normalized .= $parts['path'] ?? '/';
        if ($query) $normalized .= '?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        return $normalized;
    }
}
