#!/bin/bash
#
# Release script for laravel-caddy-metrics
# This script builds Go binaries for multiple platforms and prepares for Packagist release
#

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PACKAGE_DIR="$(dirname "$SCRIPT_DIR")"
BIN_DIR="$PACKAGE_DIR/bin"
COLLECTOR_DIR="$PACKAGE_DIR/src/Collector"

echo "🚀 Building Caddy Metrics Collector binaries..."
echo "   Package directory: $PACKAGE_DIR"
echo "   Collector source: $COLLECTOR_DIR"
echo "   Binary output: $BIN_DIR"

# Ensure bin directory exists
mkdir -p "$BIN_DIR"

# Clean old binaries
rm -f "$BIN_DIR"/caddy-metrics-collector-*

cd "$COLLECTOR_DIR"

# Build for Linux AMD64
echo "📦 Building for Linux (amd64)..."
GOOS=linux GOARCH=amd64 go build -o "$BIN_DIR/caddy-metrics-collector-linux-amd64" main.go

# Build for Linux ARM64
echo "📦 Building for Linux (arm64)..."
GOOS=linux GOARCH=arm64 go build -o "$BIN_DIR/caddy-metrics-collector-linux-arm64" main.go

# Build for macOS AMD64
echo "📦 Building for macOS (amd64)..."
GOOS=darwin GOARCH=amd64 go build -o "$BIN_DIR/caddy-metrics-collector-darwin-amd64" main.go

# Build for macOS ARM64 (Apple Silicon)
echo "📦 Building for macOS (arm64/Apple Silicon)..."
GOOS=darwin GOARCH=arm64 go build -o "$BIN_DIR/caddy-metrics-collector-darwin-arm64" main.go

# Make binaries executable
chmod +x "$BIN_DIR"/caddy-metrics-collector-*

echo ""
echo "✅ Build complete! Binaries:"
ls -la "$BIN_DIR"

echo ""
echo "📋 Next steps:"
echo "   1. Update version in composer.json if needed"
echo "   2. Commit all changes including binaries"
echo "   3. Create a git tag: git tag v1.0.0"
echo "   4. Push to GitHub: git push origin main --tags"
echo "   5. Packagist will automatically pick up the release"
