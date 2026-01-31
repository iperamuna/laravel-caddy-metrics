<?php

namespace Iperamuna\CaddyMetrics\Tests;

use Iperamuna\CaddyMetrics\CaddyMetricsServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            LivewireServiceProvider::class,
            CaddyMetricsServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('database.connections.caddy_metrics', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('caddy-metrics.database_path', ':memory:');
        $app['config']->set('app.key', 'base64:fHsc8S7Xv8I2R/7Z3o6c8M1Y6m9Q5j3A8e2X5m9B4z0=');
    }
}
