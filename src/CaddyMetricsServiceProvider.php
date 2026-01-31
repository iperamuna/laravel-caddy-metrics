<?php

namespace Iperamuna\CaddyMetrics;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Iperamuna\CaddyMetrics\Commands\InstallCaddyMetrics;
use Iperamuna\CaddyMetrics\Commands\MaintainCaddyMetrics;
use Iperamuna\CaddyMetrics\Commands\GenerateCaddyMetrics;
use Iperamuna\CaddyMetrics\Commands\ClearCaddyMetrics;
use Iperamuna\CaddyMetrics\Livewire\CaddyMetricsComponent;
use Iperamuna\CaddyMetrics\Livewire\ThreadAdvisorComponent;
use Iperamuna\CaddyMetrics\Http\Controllers\CaddyMetricsController;

class CaddyMetricsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/caddy-metrics.php', 'caddy-metrics');
    }

    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__ . '/../config/caddy-metrics.php' => config_path('caddy-metrics.php'),
        ], 'caddy-metrics-config');

        // Publish views
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/caddy-metrics'),
        ], 'caddy-metrics-views');

        // Publish stubs
        $this->publishes([
            __DIR__ . '/../resources/stubs' => resource_path('stubs/vendor/caddy-metrics'),
        ], 'caddy-metrics-stubs');

        // Publish Go collector binary
        $this->publishes([
            __DIR__ . '/../bin' => base_path('caddy-metrics'),
        ], 'caddy-metrics-binary');

        // Load views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'caddy-metrics');

        // Register Livewire components
        Livewire::component('caddy-metrics', CaddyMetricsComponent::class);
        Livewire::component('thread-advisor', ThreadAdvisorComponent::class);

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCaddyMetrics::class,
                MaintainCaddyMetrics::class,
                GenerateCaddyMetrics::class,
                ClearCaddyMetrics::class,
            ]);
        }

        // Register routes
        $this->registerRoutes();

        // Configure database connection
        $this->configureDatabaseConnection();
    }

    protected function registerRoutes(): void
    {
        if (!config('caddy-metrics.enabled')) {
            return;
        }

        Route::middleware(config('caddy-metrics.middleware', ['web']))
            ->group(function () {
                Route::get(config('caddy-metrics.dashboard_url', '/caddy/metrics'), [CaddyMetricsController::class, 'index'])
                    ->name('caddy-metrics.index');
            });
    }

    protected function configureDatabaseConnection(): void
    {
        $dbPath = config('caddy-metrics.database_path', storage_path('caddy-metrics/metrics.sqlite'));

        config([
            'database.connections.caddy_metrics' => [
                'driver' => 'sqlite',
                'database' => $dbPath,
                'prefix' => '',
                'foreign_key_constraints' => false,
            ],
        ]);
    }
}
