<?php

namespace App\Services;

use Illuminate\Http\Request;
use InvalidArgumentException;

class PrisonContext
{
    public function prison(?string $slug = null): array
    {
        $slug ??= request()->route('prison') ?: config('prisons.default');
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

    public function fromRequest(Request $request): array
    {
        return $this->prison($request->route('prison'));
    }
}
