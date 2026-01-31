<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Enable Caddy Metrics
    |--------------------------------------------------------------------------
    |
    | Set this to false to disable the Caddy Metrics dashboard.
    |
    */
    'enabled' => env('CADDY_METRICS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Dashboard URL
    |--------------------------------------------------------------------------
    |
    | The URL path where the metrics dashboard will be accessible.
    |
    */
    'dashboard_url' => env('CADDY_METRICS_DASHBOARD_URL', '/caddy/metrics'),

    /*
    |--------------------------------------------------------------------------
    | Server Name
    |--------------------------------------------------------------------------
    |
    | The server name to display on the dashboard.
    |
    */
    'server_name' => env('CADDY_METRICS_SERVER_NAME', parse_url(config('app.url'), PHP_URL_HOST) ?? 'localhost'),

    /*
    |--------------------------------------------------------------------------
    | Metrics URL
    |--------------------------------------------------------------------------
    |
    | The URL where Caddy exposes its Prometheus metrics.
    |
    */
    'metrics_url' => env('CADDY_METRICS_URL', 'http://localhost:2019/metrics'),

    /*
    |--------------------------------------------------------------------------
    | Database Path
    |--------------------------------------------------------------------------
    |
    | The path to the SQLite database used to store metrics.
    |
    */
    'database_path' => env('CADDY_METRICS_DB_PATH', storage_path('caddy-metrics/metrics.sqlite')),

    /*
    |--------------------------------------------------------------------------
    | Retention Days
    |--------------------------------------------------------------------------
    |
    | How many days to keep metrics data. Older data will be pruned.
    |
    */
    'retention_days' => env('CADDY_METRICS_RETENTION_DAYS', 7),

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | The middleware to apply to the dashboard routes.
    |
    */
    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | FrankenPHP Threads
    |--------------------------------------------------------------------------
    |
    | Your current num_threads setting in FrankenPHP.
    | Used by the Thread Advisor to provide optimization suggestions.
    |
    */
    'frankenphp_threads' => env('FRANKENPHP_THREADS', 12),

    /*
    |--------------------------------------------------------------------------
    | Caddyfile Path
    |--------------------------------------------------------------------------
    |
    | The path to your Caddyfile. Used to display the current configuration
    | in the dashboard.
    |
    */
    'caddyfile_path' => env('CADDYFILE_PATH', '/etc/frankenphp/Caddyfile'),
];
