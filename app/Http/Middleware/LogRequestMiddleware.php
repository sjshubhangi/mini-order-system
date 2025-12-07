<?php
/**
 * Senior note:
 * - Log latency and status for basic observability.
 * - Consider correlation IDs for distributed tracing (future enhancement).
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogRequestMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $start = microtime(true);
        $response = $next($request);
        $duration = round((microtime(true) - $start) * 1000);

        Log::info('API Request', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_id' => optional($request->user())->id,
            'status' => $response->status(),
            'duration_ms' => $duration,
        ]);

        return $response;
    }
}
