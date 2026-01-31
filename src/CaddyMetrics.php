<?php

namespace Iperamuna\CaddyMetrics;

use Closure;

class CaddyMetrics
{
    /**
     * The callback that should be used to authenticate Caddy Metrics users.
     *
     * @var \Closure
     */
    public static $authUsing;

    /**
     * Determine if the given request can access the Caddy Metrics dashboard.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    public static function check($request)
    {
        return (static::$authUsing ?: function ($request) {
            return app()->environment('local');
        })($request);
    }

    /**
     * Set the callback that should be used to authenticate Caddy Metrics users.
     *
     * @param  \Closure  $callback
     * @return static
     */
    public static function auth(?Closure $callback = null)
    {
        static::$authUsing = $callback;

        return new static;
    }
}
