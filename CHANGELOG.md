# Changelog

All notable changes to `laravel-caddy-metrics` will be documented in this file.

## [Unreleased]

## [1.1.0] - 2026-02-01

### Added
- **New Command: `caddy-metrics:configure`**: A helper command to easily configure `.env` variables for remote servers without needing to run the full installer.
- **Remote Server Support**: Improved documentation and tooling for deploying metrics to non-interactive remote environments.
- **Environment Variable Detection**: The configure command automatically detects and respects existing environment variables.

## [1.0.0] - 2026-01-31

### Added
- **Interactive Thread Simulator**: Real-time simulation of thread configuration using a Safety Margin slider.
- **Dynamic Thread Advising**: Smart logic to calculate `suggested_threads` based on average usage and peak bursts.
- **New Command: `caddy-metrics:clear`**: Dedicated artisan command to robustly truncate all metrics data with optional `--force`.
- **Pest Test Suite**: Comprehensive testing for advisor logic and data generation scenarios.
- **Visual Pattern Overlay**: Subtle geometric background patterns on the Advisor card for a premium dashboard feel.
- **`frankenphp_threads` Config**: New configuration option to track your current thread allocation for better analysis.

### Changed
- **Branding Refinement**: Updated "Scaling Needed" status to **"Can Scale"** with a positive green "greening" theme.
- **Improved Status Mapping**: Separated "Can Scale" (Safe Growth) from "High Load" (Amber Caution) for clearer operations.
- **Dummy Generator Math**: Recalibrated scenario math in `caddy-metrics:generate` to match actual production utilization patterns.
- **Data Initialization**: Charts now initialize immediately on page load before the first poll.
- **Compact UI**: Streamlined padding and typography in the Thread Advisor for a more utility-focused display.

### Fixed
- **JS Event Synchronization**: Resolved an issue where charts would fail to update due to shared state timing between Livewire and Chart.js.
- **Data Cleanup**: Fixed the generator command to truncate all database tables (not just metrics) before new data generation.
- **Initial Load State**: Fixed "No Data" displays occurring during the first 5 seconds of dashboard access.
