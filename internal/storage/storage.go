package storage

import (
	"database/sql"
	"fmt"
	"log"

	_ "modernc.org/sqlite"
)

type DB struct {
	Conn *sql.DB
}

func New(path string) (*DB, error) {
	conn, err := sql.Open("sqlite", path)
	if err != nil {
		return nil, err
	}

	if err := conn.Ping(); err != nil {
		return nil, err
	}

	db := &DB{Conn: conn}
	if err := db.cleanupAndMigrate(); err != nil {
		return nil, fmt.Errorf("migration failed: %w", err)
	}

	return db, nil
}

func (d *DB) Close() error {
	return d.Conn.Close()
}

func (d *DB) cleanupAndMigrate() error {
	// Check if legacy tables exist
	var legacyExists int
	// 'migrations' table often exists in Laravel
	err := d.Conn.QueryRow("SELECT count(*) FROM sqlite_master WHERE type='table' AND name='migrations'").Scan(&legacyExists)
	if err != nil {
		return err
	}

	if legacyExists > 0 {
		log.Println("Legacy database detected. Cleaning up...")
		if err := d.wipe(); err != nil {
			return err
		}
	}

	// Create new schema
	schema := `
	CREATE TABLE IF NOT EXISTS kv_store (
		key TEXT PRIMARY KEY,
		value TEXT
	);
	CREATE TABLE IF NOT EXISTS account_mappings (
		id INTEGER PRIMARY KEY AUTOINCREMENT,
		basiq_account_id TEXT UNIQUE,
		firefly_account_id TEXT,
		account_name TEXT
	);
	`
	_, err = d.Conn.Exec(schema)
	return err
}

func (d *DB) wipe() error {
	rows, err := d.Conn.Query("SELECT name FROM sqlite_master WHERE type='table'")
	if err != nil {
		return err
	}
	defer rows.Close()

	var tables []string
	for rows.Next() {
		var name string
		if err := rows.Scan(&name); err != nil {
			return err
		}
		if name != "sqlite_sequence" {
			tables = append(tables, name)
		}
	}

	for _, table := range tables {
		_, err := d.Conn.Exec("DROP TABLE IF EXISTS " + table)
		if err != nil {
			return err
		}
	}
	return nil
}
