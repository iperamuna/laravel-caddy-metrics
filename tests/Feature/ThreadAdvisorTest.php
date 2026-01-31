<?php

use Iperamuna\CaddyMetrics\Livewire\ThreadAdvisorComponent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;

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

    Config::set('caddy-metrics.frankenphp_threads', 12);
});

it('calculates optimal status correctly', function () {
    // 50% utilization: 6 goroutines for 12 threads
    insertMetrics(6);

    Livewire::test(ThreadAdvisorComponent::class)
        ->assertSet('advisor.status', 'optimal')
        ->assertSet('advisor.utilization', 50.0);
});

it('calculates can scale status correctly', function () {
    // 80% utilization: 9.6 goroutines for 12 threads
    insertMetrics(9.6);

    Livewire::test(ThreadAdvisorComponent::class)
        ->assertSet('advisor.status', 'increase')
        ->assertSet('advisor.utilization', 80.0);
});

it('calculates high load status correctly', function () {
    // 95% utilization: 11.4 goroutines for 12 threads
    insertMetrics(11.4);

    Livewire::test(ThreadAdvisorComponent::class)
        ->assertSet('advisor.status', 'caution')
        ->assertSet('advisor.utilization', 95.0);
});

it('calculates headroom status for low load', function () {
    // 10% utilization: 1.2 goroutines for 12 threads
    insertMetrics(1.2);

    Livewire::test(ThreadAdvisorComponent::class)
        ->assertSet('advisor.status', 'headroom')
        ->assertSet('advisor.utilization', 10.0);
});

it('updates suggested threads based on safety margin', function () {
    // 10 goroutines. 
    // At default 75% safety margin: (10 * 100) / 75 = 13.33 -> 14 threads
    // Peak is 10. PeakCover is 10 * 1.15 = 11.5 -> 12 threads.
    // 14 is greater than 12.
    insertMetrics(10);

    Livewire::test(ThreadAdvisorComponent::class)
        ->assertSet('safetyMargin', 75)
        ->assertSet('advisor.suggested_threads', 14)
        ->set('safetyMargin', 50) // (10 * 100) / 50 = 20 threads. Peak is 12. 20 > 12.
        ->assertSet('advisor.suggested_threads', 20);
});

function insertMetrics($value)
{
    for ($i = 0; $i < 60; $i++) {
        DB::connection('caddy_metrics')->table('metrics')->insert([
            'name' => 'go_goroutines',
            'value' => $value,
            'created_at' => now()->subMinutes($i),
        ]);

        DB::connection('caddy_metrics')->table('metrics')->insert([
            'name' => 'go_memstats_alloc_bytes',
            'value' => 1024 * 1024 * 100, // 100MB
            'created_at' => now()->subMinutes($i),
        ]);
    }
}
