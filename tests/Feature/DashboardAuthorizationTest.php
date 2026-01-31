<?php

namespace Iperamuna\CaddyMetrics\Tests\Feature;

use Illuminate\Support\Facades\Gate;
use Iperamuna\CaddyMetrics\CaddyMetrics;
use Iperamuna\CaddyMetrics\Tests\TestCase;

class DashboardAuthorizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Create the metrics table for the test connection
        $schema = "CREATE TABLE IF NOT EXISTS metrics (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            value REAL NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );";

        \Illuminate\Support\Facades\DB::connection('caddy_metrics')->unprepared($schema);

        // Reset the auth callback before each test
        CaddyMetrics::auth(null);
    }

    public function test_dashboard_is_accessible_in_local_environment_by_default()
    {
        $this->app['env'] = 'local';

        $this->get(route('caddy-metrics.index'))
            ->assertOk();
    }

    public function test_dashboard_is_forbidden_in_production_environment_by_default()
    {
        $this->app['env'] = 'production';

        $this->get(route('caddy-metrics.index'))
            ->assertForbidden();
    }

    public function test_dashboard_access_can_be_authorized_via_closure()
    {
        $this->app['env'] = 'production';

        CaddyMetrics::auth(function ($request) {
            return true;
        });

        $this->get(route('caddy-metrics.index'))
            ->assertOk();

        CaddyMetrics::auth(function ($request) {
            return false;
        });

        $this->get(route('caddy-metrics.index'))
            ->assertForbidden();
    }

    public function test_dashboard_access_can_be_authorized_via_gate()
    {
        $this->app['env'] = 'production';

        // Register the auth callback that checks the gate (simulating the ServiceProvider)
        CaddyMetrics::auth(function ($request) {
            return Gate::check('viewCaddyMetrics', [$request->user()]) || app()->environment('local');
        });

        // Define the gate to allow access
        Gate::define('viewCaddyMetrics', function ($user = null) {
            return true;
        });

        $this->get(route('caddy-metrics.index'))
            ->assertOk();

        // Define the gate to deny access
        Gate::define('viewCaddyMetrics', function ($user = null) {
            return false;
        });

        $this->get(route('caddy-metrics.index'))
            ->assertForbidden();
    }
}
