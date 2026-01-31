<?php

namespace Iperamuna\CaddyMetrics\Livewire;

use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ThreadAdvisorComponent extends Component
{
    public int $safetyMargin = 75; // Precent of capacity to use

    #[Computed]
    public function advisor(): array
    {
        $dbPath = config('caddy-metrics.database_path');

        if ($dbPath !== ':memory:' && !file_exists($dbPath)) {
            return $this->defaultAdvisor();
        }

        // Get recent metrics for analysis
        $goroutines = DB::connection('caddy_metrics')
            ->table('metrics')
            ->where('name', 'go_goroutines')
            ->orderBy('created_at', 'desc')
            ->limit(60)
            ->pluck('value');

        $memoryBytes = DB::connection('caddy_metrics')
            ->table('metrics')
            ->where('name', 'go_memstats_alloc_bytes')
            ->orderBy('created_at', 'desc')
            ->limit(60)
            ->pluck('value');

        if ($goroutines->isEmpty()) {
            return $this->defaultAdvisor();
        }

        $avgGoroutines = $goroutines->avg();
        $maxGoroutines = $goroutines->max();
        $avgMemoryMB = $memoryBytes->avg() / 1024 / 1024;

        $currentThreads = config('caddy-metrics.frankenphp_threads', 12);

        // Calculate utilization based on current threads
        $utilization = min(100, round(($avgGoroutines / $currentThreads) * 100, 1));

        // Dynamic capacity calculation based on safety margin
        // If we want to stay at X% utilization (safety margin), what should be the total threads?
        // Current Avg / Total = Margin / 100  => Total = (Avg * 100) / Margin
        $suggestedThreads = max(4, (int) ceil(($avgGoroutines * 100) / max(1, $this->safetyMargin)));

        // Also consider the peak load - we should at least cover the peak plus a small buffer
        $peakCoverThreads = (int) ceil($maxGoroutines * 1.15);
        $suggestedThreads = max($suggestedThreads, $peakCoverThreads);

        return [
            'status' => $this->getStatus($utilization),
            'current_threads' => $currentThreads,
            'suggested_threads' => $suggestedThreads,
            'avg_goroutines' => round($avgGoroutines, 1),
            'max_goroutines' => round($maxGoroutines, 0),
            'avg_memory_mb' => round($avgMemoryMB, 1),
            'utilization' => $utilization,
            'safety_margin' => $this->safetyMargin,
        ];
    }

    private function getStatus(float $utilization): string
    {
        if ($utilization > 90)
            return 'caution';
        if ($utilization > 70)
            return 'increase';
        if ($utilization < 20)
            return 'headroom';
        return 'optimal';
    }

    private function defaultAdvisor(): array
    {
        $currentThreads = config('caddy-metrics.frankenphp_threads', 12);

        return [
            'status' => 'no_data',
            'current_threads' => $currentThreads,
            'suggested_threads' => $currentThreads,
            'avg_goroutines' => 0,
            'max_goroutines' => 0,
            'avg_memory_mb' => 0,
            'utilization' => 0,
            'safety_margin' => $this->safetyMargin,
        ];
    }

    public function render()
    {
        return view('caddy-metrics::livewire.thread-advisor');
    }
}
