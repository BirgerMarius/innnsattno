<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
class NoIndexPrintPages { public function handle(Request $request, Closure $next) { $response = $next($request); if ($request->is('*/print', '*/utskrift', '*/fasit', 'print', 'print-ilseng', 'fotball-utskrift') && str_contains((string) $response->headers->get('Content-Type'), 'text/html')) $response->headers->set('X-Robots-Tag', 'noindex, follow'); return $response; } }
