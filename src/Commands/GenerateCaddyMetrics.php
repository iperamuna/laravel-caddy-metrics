<?php

namespace Iperamuna\CaddyMetrics\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use function Laravel\Prompts\info;

class GenerateCaddyMetrics extends Command
{
    protected $signature = 'caddy-metrics:generate {count=100 : Number of data points to generate}';

    protected $description = 'Generate dummy caddy metrics for testing';

    public function handle(): int
    {
        $dbPath = config('caddy-metrics.database_path', storage_path('caddy-metrics/metrics.sqlite'));

        if (!file_exists($dbPath)) {
            info("Creating database at {$dbPath}...");
            $directory = dirname($dbPath);
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
            touch($dbPath);
        }

        $schema = "CREATE TABLE IF NOT EXISTS metrics (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            value REAL NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
        CREATE INDEX IF NOT EXISTS idx_metrics_created_at ON metrics(created_at);
        CREATE INDEX IF NOT EXISTS idx_metrics_name ON metrics(name);";

        DB::connection('caddy_metrics')->unprepared($schema);

        $currentThreads = config('caddy-metrics.frankenphp_threads', 12);

        // Scenario selection
        $scenario = \Laravel\Prompts\select(
            label: 'Select a load scenario to simulate',
            options: [
                'optimal' => "System Optimal - Average ~" . ($currentThreads * 0.5) . " goroutines (50% utilization)",
                'high_load' => "Can Scale - Average ~" . ($currentThreads * 0.8) . " goroutines (80% utilization)",
                'low_load' => "Under Utilized - Average ~" . max(1, (int) ($currentThreads * 0.1)) . " goroutines (10% utilization)",
                'memory_pressure' => "High Load (Caution) - Average ~" . ($currentThreads * 0.95) . " goroutines (95% utilization)",
                'spike' => "Burst Capacity - Base ~" . ($currentThreads * 0.4) . " goroutines with periodic spikes",
            ],
            default: 'optimal'
        );

        // Suggest count based on scenario
        $suggestedCount = $this->getSuggestedCount($scenario);
        $argCount = $this->argument('count');

        $count = \Laravel\Prompts\text(
            label: 'How many data points to generate?',
            placeholder: (string) $suggestedCount,
            default: $argCount != 100 ? (string) $argCount : (string) $suggestedCount,
            hint: $this->getCountHint($scenario, $suggestedCount),
        );

        $count = (int) $count ?: $suggestedCount;

        // Truncate all tables if they have data
        $tables = DB::connection('caddy_metrics')->getSchemaBuilder()->getTableListing();
        foreach ($tables as $table) {
            if ($table === 'sqlite_sequence') {
                continue;
            }
            if (DB::connection('caddy_metrics')->table($table)->exists()) {
                info("Clearing existing data from '{$table}'...");
                DB::connection('caddy_metrics')->table($table)->truncate();
            }
        }

        info("Generating {$count} metric points with '$scenario' scenario...");

        $now = now();

        $metrics = [
            'caddy_http_request_duration_seconds_count',
            'caddy_http_response_size_bytes_sum',
            'go_goroutines',
            'go_memstats_alloc_bytes',
            'caddy_http_responses_total',
        ];

        $bases = $this->getScenarioBases($scenario, $currentThreads);

        for ($i = $count; $i >= 0; $i--) {
            $timestamp = $now->copy()->subMinutes($i * 1);

            foreach ($metrics as $metric) {
                $value = $this->generateValue($metric, $bases, $scenario, $i, $count);

                if (in_array($metric, ['caddy_http_request_duration_seconds_count', 'caddy_http_responses_total', 'caddy_http_response_size_bytes_sum'])) {
                    $bases[$metric] += rand(10, 100);
                }

                DB::connection('caddy_metrics')->table('metrics')->insert([
                    'name' => $metric,
                    'value' => abs($value),
                    'created_at' => $timestamp,
                ]);
            }
        }

        info("Done! Check the dashboard at: " . config('caddy-metrics.dashboard_url', '/caddy/metrics'));
        info("Thread Advisor should show: " . $this->getExpectedAdvisorStatus($scenario));

        return self::SUCCESS;
    }

    private function getScenarioBases(string $scenario, int $threads): array
    {
        return match ($scenario) {
            'optimal' => [
                'caddy_http_request_duration_seconds_count' => 1000,
                'caddy_http_response_size_bytes_sum' => 5000000,
                'go_goroutines' => (int) ($threads * 0.5),
                'go_memstats_alloc_bytes' => 1024 * 1024 * 150,
                'caddy_http_responses_total' => 5000,
            ],
            'high_load' => [
                'caddy_http_request_duration_seconds_count' => 5000,
                'caddy_http_response_size_bytes_sum' => 50000000,
                'go_goroutines' => (int) ($threads * 0.8),
                'go_memstats_alloc_bytes' => 1024 * 1024 * 300,
                'caddy_http_responses_total' => 50000,
            ],
            'low_load' => [
                'caddy_http_request_duration_seconds_count' => 100,
                'caddy_http_response_size_bytes_sum' => 500000,
                'go_goroutines' => (int) ($threads * 0.1),
                'go_memstats_alloc_bytes' => 1024 * 1024 * 50,
                'caddy_http_responses_total' => 500,
            ],
            'memory_pressure' => [
                'caddy_http_request_duration_seconds_count' => 3000,
                'caddy_http_response_size_bytes_sum' => 30000000,
                'go_goroutines' => (int) ($threads * 0.95),
                'go_memstats_alloc_bytes' => 1024 * 1024 * 600,
                'caddy_http_responses_total' => 30000,
            ],
            'spike' => [
                'caddy_http_request_duration_seconds_count' => 2000,
                'caddy_http_response_size_bytes_sum' => 20000000,
                'go_goroutines' => (int) ($threads * 0.4),
                'go_memstats_alloc_bytes' => 1024 * 1024 * 200,
                'caddy_http_responses_total' => 20000,
            ],
            default => [
                'caddy_http_request_duration_seconds_count' => 1000,
                'caddy_http_response_size_bytes_sum' => 5000000,
                'go_goroutines' => $threads,
                'go_memstats_alloc_bytes' => 1024 * 1024 * 100,
                'caddy_http_responses_total' => 5000,
            ],
        };
    }

    private function generateValue(string $metric, array $bases, string $scenario, int $i, int $count): float
    {
        $base = $bases[$metric];

        $jitter = match ($metric) {
            'go_goroutines' => rand(-3, 5),
            'go_memstats_alloc_bytes' => rand(-50000, 100000),
            'caddy_http_response_size_bytes_sum' => rand(-10000, 50000),
            default => rand(-10, 20),
        };

        $value = $base + $jitter;

        if ($scenario === 'spike' && $metric === 'go_goroutines') {
            if ($i % 10 < 3) {
                $value = $base * 4;
            }
        }

        return $value;
    }

    private function getExpectedAdvisorStatus(string $scenario): string
    {
        return match ($scenario) {
            'optimal' => '✅ System Optimal (green)',
            'high_load' => '⬆️ Can Scale (green)',
            'low_load' => '⬇️ Under Utilized (blue)',
            'memory_pressure' => '⚠️ High Load (amber)',
            'spike' => '⬆️ Burst Capacity (green)',
            default => 'Unknown',
        };
    }

    private function getSuggestedCount(string $scenario): int
    {
        return match ($scenario) {
            'optimal' => 60,
            'high_load' => 30,
            'low_load' => 120,
            'memory_pressure' => 45,
            'spike' => 100,
            default => 60,
        };
    }

    private function getCountHint(string $scenario, int $suggested): string
    {
        return match ($scenario) {
            'optimal' => "Suggested: {$suggested} points (~1 hour). Good for baseline display.",
            'high_load' => "Suggested: {$suggested} points (~30 min). Shows quick saturation.",
            'low_load' => "Suggested: {$suggested} points (~2 hours). Demonstrates sustained underutilization.",
            'memory_pressure' => "Suggested: {$suggested} points (~45 min). Shows memory buildup over time.",
            'spike' => "Suggested: {$suggested} points (~1.5 hours). Captures multiple burst cycles.",
            default => "Suggested: {$suggested} points.",
        };
    }
}
