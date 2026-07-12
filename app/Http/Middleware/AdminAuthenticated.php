<?php

namespace App\Http\Middleware;

use Closure;

class AdminAuthenticated
{
    public function handle($request, Closure $next)
    {
        if (! $request->session()->get('admin_authenticated')) {
            return redirect()->guest(route('admin.login'));
        }

        return $next($request);
    }
}
