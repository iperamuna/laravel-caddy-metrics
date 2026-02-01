<?php

namespace Iperamuna\CaddyMetrics\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use function Laravel\Prompts\text;
use function Laravel\Prompts\info;
use function Laravel\Prompts\note;
use function Laravel\Prompts\warning;

class ConfigureCaddyMetrics extends Command
{
    protected $signature = 'caddy-metrics:configure';

    protected $description = 'Configure Caddy Metrics environment variables for remote server';

    public function handle(): int
    {
        info('Configuring Caddy Metrics for Remote Server...');

        // 1. Port
        $port = text(
            label: 'Caddy Metrics Port',
            default: env('CADDY_METRICS_PORT', '2019'),
            placeholder: '2019',
            hint: 'The port where Caddy emits metrics (default: 2019)'
        );

        // 2. URL
        $url = text(
            label: 'Caddy Metrics URL',
            default: env('CADDY_METRICS_URL', "http://localhost:$port/metrics"),
            hint: 'The full URL to fetch metrics from'
        );

        // 3. Retention
        $retention = text(
            label: 'Retention Period (Days)',
            default: (string) env('CADDY_METRICS_RETENTION_DAYS', 7),
            hint: 'How many days to keep metrics for'
        );

        // 4. Database Path
        $dbPath = text(
            label: 'Metrics Database Path',
            default: env('CADDY_METRICS_DB_PATH', storage_path('caddy-metrics/metrics.sqlite')),
            hint: 'Absolute path to the SQLite key-value store'
        );

        // 5. Caddyfile Path
        $detectedCaddyfile = $this->detectCaddyfilePath();
        $caddyfilePath = text(
            label: 'Caddyfile Path',
            default: env('CADDYFILE_PATH', $detectedCaddyfile ?? '/etc/frankenphp/Caddyfile'),
            hint: $detectedCaddyfile
            ? "Auto-detected: $detectedCaddyfile"
            : 'Path to your FrankenPHP Caddyfile (for viewing in dashboard)'
        );

        // 6. Server Name (Optional but good for dashboard)
        $serverName = text(
            label: 'Server Name',
            default: env('CADDY_METRICS_SERVER_NAME', parse_url(config('app.url'), PHP_URL_HOST) ?? 'localhost'),
            hint: 'Name to display on the dashboard'
        );

        // Update .env
        $this->updateEnv([
            'CADDY_METRICS_PORT' => $port,
            'CADDY_METRICS_URL' => $url,
            'CADDY_METRICS_RETENTION_DAYS' => $retention,
            'CADDY_METRICS_DB_PATH' => $dbPath,
            'CADDYFILE_PATH' => $caddyfilePath,
            'CADDY_METRICS_SERVER_NAME' => $serverName,
        ]);

        // Ensure storage directory exists
        $storageDir = dirname($dbPath);
        if (!is_dir($storageDir)) {
            if (mkdir($storageDir, 0755, true)) {
                note("Created storage directory: $storageDir");
            } else {
                warning("Failed to create storage directory: $storageDir");
            }
        }

        info('Configuration complete! Environment variables updated.');
        note('Make sure to clear your config cache if you are using it: php artisan config:clear');

        return self::SUCCESS;
    }

    private function detectCaddyfilePath(): ?string
    {
        // Common Caddyfile locations to check
        $possiblePaths = [
            '/etc/frankenphp/Caddyfile',
            '/etc/caddy/Caddyfile',
            base_path('Caddyfile'),
            '/usr/local/etc/frankenphp/Caddyfile',
        ];

        // Also check for project-specific paths like /etc/frankenphp/{project-name}/Caddyfile
        $projectPath = base_path();
        if ($projectPath) {
            $projectName = basename($projectPath);
            $possiblePaths[] = "/etc/frankenphp/{$projectName}/Caddyfile";
            $possiblePaths[] = "/etc/frankenphp/" . str_replace('-', '_', $projectName) . "/Caddyfile";
        }

        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    private function updateEnv(array $values): void
    {
        $envPath = base_path('.env');

        if (!File::exists($envPath)) {
            warning('.env file not found. Skipping .env update.');
            return;
        }

        $content = File::get($envPath);

        foreach ($values as $key => $value) {
            // Check if key already exists
            if (preg_match("/^{$key}=/m", $content)) {
                // Replace existing value
                $content = preg_replace(
                    "/^{$key}=.*/m",
                    "{$key}={$value}",
                    $content
                );
            } else {
                // Add new key
                $content .= "\n{$key}={$value}";
            }
        }

        File::put($envPath, $content);
        note('Updated .env file with new configuration.');
    }
}
