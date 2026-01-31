<?php

use Iperamuna\CaddyMetrics\Commands\GenerateCaddyMetrics;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

uses(Iperamuna\CaddyMetrics\Tests\TestCase::class);

beforeEach(function () {
    // Setup the mock database
    DB::connection('caddy_metrics')->unprepared("
        CREATE TABLE IF NOT EXISTS metrics (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            value REAL NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
    ");
});

it('can generate optimal scenario metrics', function () {
    $this->artisan('caddy-metrics:generate 10')
        ->expectsChoice('Select a load scenario to simulate', 'optimal', [
            'optimal' => 'System Optimal - Average ~6 goroutines (50% utilization)',
            'high_load' => 'Can Scale - Average ~9.6 goroutines (80% utilization)',
            'low_load' => 'Under Utilized - Average ~1 goroutines (10% utilization)',
            'memory_pressure' => 'High Load (Caution) - Average ~11.4 goroutines (95% utilization)',
            'spike' => 'Burst Capacity - Base ~4.8 goroutines with periodic spikes',
        ])
        ->expectsQuestion('How many data points to generate?', '10')
        ->assertExitCode(0);

    $count = DB::connection('caddy_metrics')->table('metrics')->where('name', 'go_goroutines')->count();
    expect($count)->toBeGreaterThan(0);
});

it('truncates tables before generation', function () {
    // Pre-insert data
    DB::connection('caddy_metrics')->table('metrics')->insert([
        'name' => 'test',
        'value' => 1,
    ]);

    $this->artisan('caddy-metrics:generate 10')
        ->expectsChoice('Select a load scenario to simulate', 'optimal', [
            'optimal' => 'System Optimal - Average ~6 goroutines (50% utilization)',
            'high_load' => 'Can Scale - Average ~9.6 goroutines (80% utilization)',
            'low_load' => 'Under Utilized - Average ~1 goroutines (10% utilization)',
            'memory_pressure' => 'High Load (Caution) - Average ~11.4 goroutines (95% utilization)',
            'spike' => 'Burst Capacity - Base ~4.8 goroutines with periodic spikes',
        ])
        ->expectsQuestion('How many data points to generate?', '10')
        ->assertExitCode(0);

    $testCount = DB::connection('caddy_metrics')->table('metrics')->where('name', 'test')->count();
    expect($testCount)->toBe(0);
});
