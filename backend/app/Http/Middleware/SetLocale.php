<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $supported = (array) config('app.supported_locales', ['en']);
        $requested = (string) $request->query('lang', '');

        if (in_array($requested, $supported, true)) {
            app()->setLocale($requested);
        }

        return $next($request);
    }
}
