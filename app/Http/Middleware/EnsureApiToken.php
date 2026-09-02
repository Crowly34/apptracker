<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiToken
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('apptracker.token');

        if (! is_string($expected) || $expected === '') {
            abort(500, 'APPTRACKER_TOKEN is not configured.');
        }

        $provided = $request->bearerToken() ?? '';

        if (! hash_equals($expected, $provided)) {
            abort(401, 'Invalid API token.');
        }

        return $next($request);
    }
}
