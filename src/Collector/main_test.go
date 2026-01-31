package main

import (
	"database/sql"
	"io/ioutil"
	"os"
	"testing"

	_ "modernc.org/sqlite"
)

func TestParseMetrics(t *testing.T) {
	input := `
# HELP caddy_http_request_duration_seconds_count
# TYPE caddy_http_request_duration_seconds_count counter
caddy_http_request_duration_seconds_count{server="srv0"} 123
caddy_http_request_duration_seconds_count{server="srv1"} 100
# HELP go_goroutines Number of goroutines that currently exist.
# TYPE go_goroutines gauge
go_goroutines 15
go_memstats_alloc_bytes 1048576
`

	expected := map[string]float64{
		"caddy_http_request_duration_seconds_count": 223,
		"go_goroutines":           15,
		"go_memstats_alloc_bytes": 1048576,
	}

	results := parseMetrics(input)

	for key, val := range expected {
		if results[key] != val {
			t.Errorf("Expected %s to be %f, got %f", key, val, results[key])
		}
	}

	// Verify unwanted metrics are ignored
	if _, ok := results["caddy_ignored_metric"]; ok {
		t.Errorf("Expected caddy_ignored_metric to be ignored")
	}
}

func TestPrune(t *testing.T) {
	// Create temporary database
	tmpfile, err := ioutil.TempFile("", "metrics_test_*.sqlite")
	if err != nil {
		t.Fatal(err)
	}
	defer os.Remove(tmpfile.Name()) // clean up

	db, err := sql.Open("sqlite", tmpfile.Name())
	if err != nil {
		t.Fatal(err)
	}
	defer db.Close()

	// Create table
	schema := `
	CREATE TABLE metrics (
		id INTEGER PRIMARY KEY AUTOINCREMENT,
		name TEXT NOT NULL,
		value REAL NOT NULL,
		created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
	);`
	_, err = db.Exec(schema)
	if err != nil {
		t.Fatal(err)
	}

	// Insert old record (10 days ago)
	_, err = db.Exec("INSERT INTO metrics (name, value, created_at) VALUES ('go_goroutines', 10, datetime('now', '-10 days'))")
	if err != nil {
		t.Fatal(err)
	}

	// Insert new record (now)
	_, err = db.Exec("INSERT INTO metrics (name, value, created_at) VALUES ('go_goroutines', 20, datetime('now'))")
	if err != nil {
		t.Fatal(err)
	}

	// Prune older than 7 days
	prune(db, 7)

	// Verify counts
	var count int
	err = db.QueryRow("SELECT COUNT(*) FROM metrics").Scan(&count)
	if err != nil {
		t.Fatal(err)
	}

	if count != 1 {
		t.Errorf("Expected 1 record remaining, got %d", count)
	}

	// Verify the remaining record is the new one
	var value float64
	err = db.QueryRow("SELECT value FROM metrics").Scan(&value)
	if err != nil {
		t.Fatal(err)
	}

	if value != 20 {
		t.Errorf("Expected remaining record value 20, got %f", value)
	}
}
