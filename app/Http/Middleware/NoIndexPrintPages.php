<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class NoIndexPrintPages
{
    public function handle(Request $request, Closure $next)
    {
        $isPrintPage = $request->is('*/print', '*/utskrift', '*/fasit', 'print', 'print-ilseng', 'fotball-utskrift');

        // Debugbar is appended as global middleware by its service provider.
        // Disable it before the remaining middleware runs, but only for print pages.
        if ($isPrintPage && app()->bound('debugbar')) {
            app('debugbar')->disable();
        }

        $response = $next($request);

        if ($isPrintPage && str_contains((string) $response->headers->get('Content-Type'), 'text/html')) {
            $response->headers->set('X-Robots-Tag', 'noindex, follow');
        }

        return $response;
    }
}
