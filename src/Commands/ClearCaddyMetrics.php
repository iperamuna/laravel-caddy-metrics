<?php

namespace Iperamuna\CaddyMetrics\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use function Laravel\Prompts\info;
use function Laravel\Prompts\confirm;

class ClearCaddyMetrics extends Command
{
    protected $signature = 'caddy-metrics:clear {--force : Clear metrics without confirmation}';

    protected $description = 'Clear all stored caddy metrics';

    public function handle(): int
    {
        $dbPath = config('caddy-metrics.database_path', storage_path('caddy-metrics/metrics.sqlite'));

        if (!file_exists($dbPath)) {
            info("Database not found at {$dbPath}. Nothing to clear.");
            return self::SUCCESS;
        }

        if (!$this->option('force')) {
            $confirmed = confirm(
                label: 'Are you sure you want to clear all caddy metrics data?',
                default: false,
                yes: 'Yes, clear everything',
                no: 'No, keep it',
                hint: 'This will truncate all tables in the metrics database.'
            );

            if (!$confirmed) {
                info('Clear operation cancelled.');
                return self::SUCCESS;
            }
        }

        $tables = DB::connection('caddy_metrics')->getSchemaBuilder()->getTableListing();
        $cleared = 0;

        foreach ($tables as $table) {
            if ($table === 'sqlite_sequence') {
                continue;
            }

            if (DB::connection('caddy_metrics')->table($table)->exists()) {
                info("Clearing data from '{$table}'...");
                DB::connection('caddy_metrics')->table($table)->truncate();
                $cleared++;
            }
        }

        if ($cleared === 0) {
            info('Database was already empty.');
        } else {
            info('Successfully cleared all metrics data.');
        }

        return self::SUCCESS;
    }
}
