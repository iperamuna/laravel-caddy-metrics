<?php

namespace Iperamuna\CaddyMetrics\Commands;

use Illuminate\Console\Command;
use function Laravel\Prompts\info;
use function Laravel\Prompts\warning;
use function Laravel\Prompts\error;
use function Laravel\Prompts\select;

class MaintainCaddyMetrics extends Command
{
    protected $signature = 'caddy-metrics:maintain {action? : The action to perform (start, stop, restart, status, enable, disable)}';

    protected $description = 'Manage the Caddy Metrics service';

    public function handle(): int
    {
        $action = $this->argument('action');

        $isRoot = function_exists('posix_geteuid') ? posix_geteuid() === 0 : false;
        if (!$isRoot) {
            warning('This command should be run as root or with sudo to manage system services.');
        }

        $os = PHP_OS_FAMILY;
        info("Detected OS: $os");

        if (!$action) {
            $action = select(
                label: 'What would you like to do?',
                options: ['start', 'stop', 'restart', 'status', 'enable', 'disable'],
                default: 'status'
            );
        }

        if ($os === 'Darwin') {
            return $this->handleMacOS($action);
        } elseif ($os === 'Linux') {
            return $this->handleLinux($action);
        } else {
            error("Unsupported OS: $os");
            return self::FAILURE;
        }
    }

    private function handleMacOS(string $action): int
    {
        $plistName = 'com.caddy-metrics.plist';
        $systemPath = "/Library/LaunchDaemons/$plistName";
        $userPath = ($_SERVER['HOME'] ?? '/tmp') . "/Library/LaunchAgents/$plistName";

        $targetPath = file_exists($systemPath) ? $systemPath : (file_exists($userPath) ? $userPath : null);

        if (!$targetPath) {
            error("Service file not found. Have you run 'caddy-metrics:install'?");
            return self::FAILURE;
        }

        info("Managing service at: $targetPath");

        switch ($action) {
            case 'start':
                $this->executeSystemCmd("launchctl load -w $targetPath");
                break;
            case 'stop':
                $this->executeSystemCmd("launchctl unload $targetPath");
                break;
            case 'restart':
                $this->executeSystemCmd("launchctl unload $targetPath");
                sleep(1);
                $this->executeSystemCmd("launchctl load -w $targetPath");
                break;
            case 'status':
                $this->executeSystemCmd("launchctl list | grep caddy-metrics", true);
                break;
            case 'enable':
                $this->executeSystemCmd("launchctl load -w $targetPath");
                break;
            case 'disable':
                $this->executeSystemCmd("launchctl unload -w $targetPath");
                break;
        }

        return self::SUCCESS;
    }

    private function handleLinux(string $action): int
    {
        $serviceName = 'caddy-metrics';

        exec("systemctl list-unit-files $serviceName.service 2>/dev/null", $output, $returnVar);
        $serviceExists = false;
        foreach ($output as $line) {
            if (str_contains($line, $serviceName)) {
                $serviceExists = true;
                break;
            }
        }

        if (!$serviceExists) {
            error("Service '$serviceName' not found. Have you run 'caddy-metrics:install'?");
            return self::FAILURE;
        }

        switch ($action) {
            case 'start':
            case 'stop':
            case 'restart':
            case 'enable':
            case 'disable':
            case 'status':
                $this->executeSystemCmd("systemctl $action $serviceName", $action === 'status');
                break;
        }

        return self::SUCCESS;
    }

    private function executeSystemCmd(string $cmd, bool $ignoreFailure = false): void
    {
        info("Running: $cmd");
        exec($cmd, $output, $returnVar);

        if ($returnVar !== 0 && !$ignoreFailure) {
            warning("Command failed with exit code $returnVar");
        }

        foreach ($output as $line) {
            $this->line($line);
        }
    }
}
