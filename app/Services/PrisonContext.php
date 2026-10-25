<?php

namespace App\Services;

use Illuminate\Http\Request;
use InvalidArgumentException;

class PrisonContext
{
    public function prison(?string $slug = null): array
    {
        $slug ??= $this->slugFromRequest(request());
        $prison = config("prisons.all.{$slug}");

        if (! is_array($prison)) {
            throw new InvalidArgumentException("Ukjent fengsel: {$slug}");
        }

        return ['slug' => $slug] + $prison;
    }

    public function url(string $path = '', ?string $slug = null): string
    {
        return '/'.$this->prison($slug)['slug'].($path === '' ? '' : '/'.ltrim($path, '/'));
    }

    /** Add the current prison to a shared URL without changing prefixed URLs. */
    public function contextUrl(string $url, ?string $slug = null): string
    {
        $parts = parse_url($url);

        if ($parts === false || ! isset($parts['path'])) {
            return $url;
        }

        $isAbsolute = isset($parts['scheme'], $parts['host']);
        if (($isAbsolute && $parts['host'] !== request()->getHost()) || (! $isAbsolute && ! str_starts_with($url, '/'))) {
            return $url;
        }

        $path = $parts['path'] ?? '';
        $slugs = implode('|', array_map('preg_quote', array_keys(config('prisons.all'))));
        if (preg_match('#^/(?:'.$slugs.')(?:/|$)#', $path)) {
            return $url;
        }

        parse_str($parts['query'] ?? '', $query);
        $query['prison'] = $this->prison($slug)['slug'];

        $prefix = $isAbsolute
            ? $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '')
            : '';

        return $prefix.$path.'?'.http_build_query($query).(isset($parts['fragment']) ? '#'.$parts['fragment'] : '');
    }

    public function homeUrl(?string $slug = null): string
    {
        return '/'.$this->prison($slug)['slug'];
    }

    public function fromRequest(Request $request): array
    {
        return $this->prison($this->slugFromRequest($request));
    }

    private function slugFromRequest(Request $request): string
    {
        $routeSlug = $request->route('prison');
        if (is_string($routeSlug) && is_array(config("prisons.all.{$routeSlug}"))) {
            return $routeSlug;
        }

        $querySlug = $request->query('prison');
        if (is_string($querySlug) && is_array(config("prisons.all.{$querySlug}"))) {
            return $querySlug;
        }

        return config('prisons.default');
    }
}
