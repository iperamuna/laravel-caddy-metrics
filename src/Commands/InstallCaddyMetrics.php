<?php

namespace Iperamuna\CaddyMetrics\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use function Laravel\Prompts\text;
use function Laravel\Prompts\info;
use function Laravel\Prompts\warning;
use function Laravel\Prompts\note;
use function Laravel\Prompts\confirm;

class InstallCaddyMetrics extends Command
{
    protected $signature = 'caddy-metrics:install';

    protected $description = 'Install and configure the Caddy Metrics service';

    public function handle(): int
    {
        info('Welcome to the Caddy Metrics Installer!');

        $os = PHP_OS_FAMILY;
        info("Detected OS: $os");

        if ($os !== 'Darwin' && $os !== 'Linux') {
            warning("Unsupported OS detected: $os. Proceeding with generic assumptions.");
        }

        $isRoot = function_exists('posix_geteuid') ? posix_geteuid() === 0 : false;

        if (!$isRoot) {
            if ($os === 'Linux') {
                warning('On Linux, this command typically requires root privileges to manage systemd.');
                if (!confirm("You are not running as root. Continue anyway?", default: false)) {
                    return self::FAILURE;
                }
            }
        }

        $port = text(
            label: 'Caddy Metrics Port',
            default: '2019',
            placeholder: '2019',
            hint: 'The port where Caddy emits metrics (default: 2019)'
        );

        $url = text(
            label: 'Caddy Metrics URL',
            default: "http://localhost:$port/metrics",
            hint: 'The full URL to fetch metrics from'
        );

        $defaultUser = 'www-data';
        if ($os === 'Darwin') {
            $defaultUser = get_current_user();
        } elseif (getenv('SUDO_USER')) {
            $defaultUser = getenv('SUDO_USER');
        }

        $user = text(
            label: 'Service User',
            default: $defaultUser,
            hint: 'The system user that will run the collector daemon'
        );

        $defaultGroup = $user;
        if ($os === 'Darwin') {
            $defaultGroup = 'staff';
        }

        $group = text(
            label: 'Service Group',
            default: $defaultGroup,
            hint: 'The system group for the collector daemon'
        );

        $retention = text(
            label: 'Retention Period (Days)',
            default: (string) config('caddy-metrics.retention_days', 7),
            hint: 'How many days to keep metrics for'
        );

        // Auto-detect Caddyfile path
        $detectedCaddyfile = $this->detectCaddyfilePath();

        $caddyfilePath = text(
            label: 'Caddyfile Path',
            default: $detectedCaddyfile ?? '/etc/frankenphp/Caddyfile',
            hint: $detectedCaddyfile
            ? "Auto-detected: $detectedCaddyfile"
            : 'Path to your FrankenPHP Caddyfile (for viewing in dashboard)'
        );

        // Determine binary path - check package bin first, then published location
        $binaryPath = $this->findBinaryPath($os);
        $dbPath = config('caddy-metrics.database_path', storage_path('caddy-metrics/metrics.sqlite'));
        $workingDir = dirname($binaryPath);
        $logPath = storage_path('logs');

        // Update .env with Caddyfile path
        $this->updateEnvFile('CADDYFILE_PATH', $caddyfilePath);

        if (!File::exists($binaryPath)) {
            warning("The collector binary was not found at [$binaryPath].");
            note("Run 'php artisan vendor:publish --tag=caddy-metrics-binary' to publish the binary.");
            if (!confirm('Do you want to continue anyway?')) {
                return self::FAILURE;
            }
        }

        // Ensure storage directory exists
        $storageDir = dirname($dbPath);
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0755, true);
        }

        if ($os === 'Darwin') {
            return $this->installMacOS($user, $group, $binaryPath, $url, $dbPath, $workingDir, $logPath, $retention);
        } else {
            return $this->installLinux($user, $group, $binaryPath, $url, $dbPath, $workingDir, $retention);
        }
    }

    private function findBinaryPath(string $os): string
    {
        $arch = php_uname('m');

        // Map architecture names
        if ($arch === 'arm64' || $arch === 'aarch64') {
            $archSuffix = 'arm64';
        } else {
            $archSuffix = 'amd64';
        }

        $osSuffix = $os === 'Darwin' ? 'darwin' : 'linux';
        $binaryName = "caddy-metrics-collector-{$osSuffix}-{$archSuffix}";

        // Check published location first
        $publishedPath = base_path("caddy-metrics/{$binaryName}");
        if (File::exists($publishedPath)) {
            return $publishedPath;
        }

        // Check package bin directory
        $packagePath = __DIR__ . "/../../bin/{$binaryName}";
        if (File::exists($packagePath)) {
            return $packagePath;
        }

        // Fallback to generic name
        return base_path('caddy-metrics/caddy-metrics-collector');
    }

    private function installMacOS($user, $group, $binaryPath, $url, $dbPath, $workingDir, $logPath, $retention): int
    {
        $stubPath = __DIR__ . '/../../resources/stubs/com.caddy-metrics.plist.stub';
        if (!File::exists($stubPath)) {
            $this->error("Stub file not found at [$stubPath].");
            return self::FAILURE;
        }

        $isRoot = function_exists('posix_geteuid') ? posix_geteuid() === 0 : false;

        if ($isRoot) {
            $plistDest = '/Library/LaunchDaemons/com.caddy-metrics.plist';
            info("Installing as System Daemon to $plistDest");
        } else {
            $plistDest = $_SERVER['HOME'] . '/Library/LaunchAgents/com.caddy-metrics.plist';
            info("Installing as User Agent to $plistDest");
        }

        $userNameKey = $isRoot ? "<key>UserName</key>\n    <string>$user</string>" : "";

        $stub = File::get($stubPath);
        $content = str_replace(
            ['{{BINARY_PATH}}', '{{METRICS_URL}}', '{{DB_PATH}}', '{{RETENTION_DAYS}}', '{{LOG_PATH}}', '{{USER_KEY}}'],
            [$binaryPath, $url, $dbPath, $retention, $logPath, $userNameKey],
            $stub
        );

        $dir = dirname($plistDest);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        File::put($plistDest, $content);

        if (confirm("Do you want to load and start the service now?", default: true)) {
            exec("launchctl unload $plistDest 2>/dev/null");
            $this->runSystemCommand("launchctl load -w $plistDest");
            note("Service loaded! Check logs at $logPath/caddy-metrics.log");
        }

        $this->installServiceProvider();

        info('Installation complete!');
        return self::SUCCESS;
    }

    private function installLinux($user, $group, $binaryPath, $url, $dbPath, $workingDir, $retention): int
    {
        $stubPath = __DIR__ . '/../../resources/stubs/caddy-metrics.service.stub';
        if (!File::exists($stubPath)) {
            $this->error("Stub file not found at [$stubPath].");
            return self::FAILURE;
        }

        $stub = File::get($stubPath);
        $serviceContent = str_replace(
            ['{{USER}}', '{{GROUP}}', '{{BINARY_PATH}}', '{{METRICS_URL}}', '{{DB_PATH}}', '{{WORKING_DIR}}', '{{RETENTION_DAYS}}'],
            [$user, $group, $binaryPath, $url, $dbPath, $workingDir, $retention],
            $stub
        );

        $servicePath = '/etc/systemd/system/caddy-metrics.service';

        $isRoot = function_exists('posix_geteuid') ? posix_geteuid() === 0 : false;
        if (!$isRoot) {
            warning("Cannot write to $servicePath without root privileges.");
            $this->info("Here is the service file content:\n\n" . $serviceContent);
            return self::FAILURE;
        }

        info("Generating service file at: $servicePath");
        File::put($servicePath, $serviceContent);

        if (confirm("Do you want to reload systemd and start the service now?", default: true)) {
            $this->runSystemCommand('systemctl daemon-reload');
            $this->runSystemCommand('systemctl enable caddy-metrics');
            $this->runSystemCommand('systemctl start caddy-metrics');
            $this->runSystemCommand('systemctl status caddy-metrics --no-pager');
        }

        $this->installServiceProvider();

        info('Installation complete!');
        return self::SUCCESS;
    }

    private function runSystemCommand($command): void
    {
        info("Running: $command");
        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            warning("Command failed with exit code $returnVar");
        }
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
        $projectName = basename(base_path());
        $projectPaths = [
            "/etc/frankenphp/{$projectName}/Caddyfile",
            "/etc/frankenphp/" . str_replace('-', '_', $projectName) . "/Caddyfile",
        ];

        $allPaths = array_merge($projectPaths, $possiblePaths);

        foreach ($allPaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    private function updateEnvFile(string $key, string $value): void
    {
        $envPath = base_path('.env');

        if (!File::exists($envPath)) {
            warning('.env file not found. Please add CADDYFILE_PATH manually.');
            return;
        }

        $content = File::get($envPath);

        // Check if key already exists
        if (preg_match("/^{$key}=/m", $content)) {
            // Replace existing value
            $content = preg_replace(
                "/^{$key}=.*/m",
                "{$key}={$value}",
                $content
            );
            note("Updated {$key} in .env");
        } else {
            // Add new key
            $content .= "\n{$key}={$value}\n";
            note("Added {$key} to .env");
        }

        File::put($envPath, $content);
    }

    private function installServiceProvider(): void
    {
        $this->call('vendor:publish', [
            '--tag' => 'caddy-metrics-provider',
        ]);

        if (file_exists(base_path('bootstrap/providers.php')) && method_exists(ServiceProvider::class, 'addProviderToBootstrapFile')) {
            ServiceProvider::addProviderToBootstrapFile('App\Providers\CaddyMetricsServiceProvider');
            note('Registered CaddyMetricsServiceProvider in bootstrap/providers.php');
        }
    }
}
