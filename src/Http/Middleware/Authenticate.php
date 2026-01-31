<?php

namespace Iperamuna\CaddyMetrics\Http\Middleware;

use Iperamuna\CaddyMetrics\CaddyMetrics;

class Authenticate
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Illuminate\Http\Response|null
     */
    public function handle($request, $next)
    {
        return CaddyMetrics::check($request) ? $next($request) : abort(403);
    }
}
