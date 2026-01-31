package main

import (
	"database/sql"
	"flag"
	"io"
	"log"
	"net/http"
	"strconv"
	"strings"
	"time"

	_ "modernc.org/sqlite"
)

func main() {
	metricsURL := flag.String("url", "http://localhost:2019/metrics", "URL to fetch Caddy metrics from")
	dbPath := flag.String("db", "storage/caddy-metrics/metrics.sqlite", "Path to SQLite database")
	interval := flag.Duration("interval", 15*time.Second, "Polling interval")
	retention := flag.Int("retention", 7, "Retention period in days")
	flag.Parse()

	log.Printf("Starting Caddy Metrics Collector...")
	log.Printf("Metrics URL: %s", *metricsURL)
	log.Printf("Database: %s", *dbPath)
	log.Printf("Retention: %d days", *retention)

	// Open Database
	db, err := sql.Open("sqlite", *dbPath)
	if err != nil {
		log.Fatalf("Failed to open database: %v", err)
	}
	defer db.Close()

	// Create Table
	schema := `
	CREATE TABLE IF NOT EXISTS metrics (
		id INTEGER PRIMARY KEY AUTOINCREMENT,
		name TEXT NOT NULL,
		value REAL NOT NULL,
		created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
	);
	CREATE INDEX IF NOT EXISTS idx_metrics_created_at ON metrics(created_at);
	CREATE INDEX IF NOT EXISTS idx_metrics_name ON metrics(name);
	`
	_, err = db.Exec(schema)
	if err != nil {
		log.Fatalf("Failed to create schema: %v", err)
	}

	ticker := time.NewTicker(*interval)
	defer ticker.Stop()

	// Pruning ticker (check every hour)
	pruneTicker := time.NewTicker(1 * time.Hour)
	defer pruneTicker.Stop()

	// Initial fetch
	collect(db, *metricsURL)
	prune(db, *retention) // Initial prune

	go func() {
		for range pruneTicker.C {
			prune(db, *retention)
		}
	}()

	for range ticker.C {
		collect(db, *metricsURL)
	}
}

func prune(db *sql.DB, days int) {
	if days <= 0 {
		return
	}
	_, err := db.Exec("DELETE FROM metrics WHERE created_at < datetime('now', '-' || ? || ' days')", days)
	if err != nil {
		log.Printf("Error pruning old metrics: %v", err)
	}
}

func collect(db *sql.DB, url string) {
	resp, err := http.Get(url)
	if err != nil {
		log.Printf("Error fetching metrics: %v", err)
		return
	}
	defer resp.Body.Close()

	body, err := io.ReadAll(resp.Body)
	if err != nil {
		log.Printf("Error reading metrics body: %v", err)
		return
	}

	metrics := parseMetrics(string(body))
	if len(metrics) == 0 {
		return
	}

	tx, err := db.Begin()
	if err != nil {
		log.Printf("Error starting transaction: %v", err)
		return
	}

	stmt, err := tx.Prepare("INSERT INTO metrics (name, value, created_at) VALUES (?, ?, datetime('now'))")
	if err != nil {
		log.Printf("Error preparing statement: %v", err)
		tx.Rollback()
		return
	}
	defer stmt.Close()

	count := 0
	for name, value := range metrics {
		_, err := stmt.Exec(name, value)
		if err != nil {
			log.Printf("Error inserting metric %s: %v", name, err)
		} else {
			count++
		}
	}

	err = tx.Commit()
	if err != nil {
		log.Printf("Error committing transaction: %v", err)
	} else {
		log.Printf("Collected %d metrics", count)
	}
}

// We interpret only specific interesting metrics
func parseMetrics(data string) map[string]float64 {
	results := make(map[string]float64)
	lines := strings.Split(data, "\n")

	// Regex for simple prometheus lines: name value
	// Ignoring labels for now to simplify

	// We specifically want:
	// caddy_http_request_duration_seconds_count
	// caddy_http_response_size_bytes_sum

	wanted := map[string]bool{
		"caddy_http_request_duration_seconds_count": true,
		"caddy_http_response_size_bytes_sum":        true,
		"go_goroutines":                             true,
		"go_memstats_alloc_bytes":                   true,
		"caddy_http_responses_total":                true,
	}

	for _, line := range lines {
		if strings.HasPrefix(line, "#") || len(strings.TrimSpace(line)) == 0 {
			continue
		}

		parts := strings.Fields(line)
		if len(parts) < 2 {
			continue
		}

		name := parts[0]

		// Handle labels: caddy_http_response_size_bytes_sum{...}
		if idx := strings.Index(name, "{"); idx != -1 {
			// for now, let's just ignore specific labels and sum them up or just take the base name
			// Use simple logic: if it's one of our wanted base metrics, we aggregate it?
			// Actually, Caddy metrics usually have labels.
			// Ideally we want to sum up all 'caddy_http_request_duration_seconds_count' regardless of handler
			// But for simplicity in this MVP, let's just match the prefix

			baseName := name[:idx]
			if wanted[baseName] {
				val, err := strconv.ParseFloat(parts[1], 64)
				if err == nil {
					results[baseName] += val
				}
			}
		} else {
			// No labels
			if wanted[name] {
				val, err := strconv.ParseFloat(parts[1], 64)
				if err == nil {
					results[name] = val
				}
			}
		}
	}
	return results
}
