<?php

namespace Iperamuna\CaddyMetrics\Livewire;

use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

class CaddyMetricsComponent extends Component
{
    public bool $showCaddyfile = false;

    public function toggleCaddyfile(): void
    {
        $this->showCaddyfile = !$this->showCaddyfile;
    }

    #[Computed]
    public function caddyfileContent(): array
    {
        $path = config('caddy-metrics.caddyfile_path', '/etc/frankenphp/Caddyfile');

        if (!file_exists($path)) {
            return [
                'exists' => false,
                'path' => $path,
                'content' => null,
                'error' => "Caddyfile not found at: {$path}",
            ];
        }

        $content = @file_get_contents($path);

        if ($content === false) {
            return [
                'exists' => true,
                'path' => $path,
                'content' => null,
                'error' => "Unable to read Caddyfile. Check file permissions.",
            ];
        }

        return [
            'exists' => true,
            'path' => $path,
            'content' => $content,
            'error' => null,
        ];
    }

    #[Computed]
    public function metrics(): array
    {
        $dbPath = config('caddy-metrics.database_path');

        if (!file_exists($dbPath)) {
            return [];
        }

        $metricNames = DB::connection('caddy_metrics')
            ->table('metrics')
            ->select('name')
            ->distinct()
            ->pluck('name');

        $result = [];

        foreach ($metricNames as $name) {
            $points = DB::connection('caddy_metrics')
                ->table('metrics')
                ->where('name', $name)
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get(['value', 'created_at'])
                ->reverse()
                ->values();

            $result[$name] = [
                'labels' => $points->pluck('created_at')->map(fn($dt) => \Carbon\Carbon::parse($dt)->format('H:i'))->toArray(),
                'values' => $points->pluck('value')->toArray(),
            ];
        }

        return $result;
    }


    public function rendering($view, $data): void
    {
        $this->dispatch('metrics-updated', metrics: $this->metrics);
    }

    public function render()
    {
        return view('caddy-metrics::livewire.caddy-metrics');
    }
}
