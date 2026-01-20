package storage

import (
	"database/sql"
)

// SetKV stores a key-value pair
func (d *DB) SetKV(key, value string) error {
	query := `INSERT INTO kv_store (key, value) VALUES (?, ?)
	          ON CONFLICT(key) DO UPDATE SET value = excluded.value`
	_, err := d.Conn.Exec(query, key, value)
	return err
}

// GetKV retrieves a value by key
func (d *DB) GetKV(key string) (string, error) {
	var value string
	err := d.Conn.QueryRow("SELECT value FROM kv_store WHERE key = ?", key).Scan(&value)
	if err == sql.ErrNoRows {
		return "", nil
	}
	return value, err
}

// AccountMapping represents a link between Basiq and Firefly
type AccountMapping struct {
	ID               int
	BasiqAccountID   string
	FireflyAccountID string
	AccountName      string
}

// SaveMapping saves or updates an account mapping
func (d *DB) SaveMapping(mapping AccountMapping) error {
	query := `INSERT INTO account_mappings (basiq_account_id, firefly_account_id, account_name)
	          VALUES (?, ?, ?)
	          ON CONFLICT(basiq_account_id) DO UPDATE SET
	          firefly_account_id = excluded.firefly_account_id,
	          account_name = excluded.account_name`
	_, err := d.Conn.Exec(query, mapping.BasiqAccountID, mapping.FireflyAccountID, mapping.AccountName)
	return err
}

// GetMappings returns all account mappings
func (d *DB) GetMappings() ([]AccountMapping, error) {
	rows, err := d.Conn.Query("SELECT id, basiq_account_id, firefly_account_id, account_name FROM account_mappings")
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var mappings []AccountMapping
	for rows.Next() {
		var m AccountMapping
		if err := rows.Scan(&m.ID, &m.BasiqAccountID, &m.FireflyAccountID, &m.AccountName); err != nil {
			return nil, err
		}
		mappings = append(mappings, m)
	}
	return mappings, nil
}

// GetMappingByBasiqID returns a single mapping
func (d *DB) GetMappingByBasiqID(basiqID string) (*AccountMapping, error) {
	var m AccountMapping
	err := d.Conn.QueryRow("SELECT id, basiq_account_id, firefly_account_id, account_name FROM account_mappings WHERE basiq_account_id = ?", basiqID).Scan(&m.ID, &m.BasiqAccountID, &m.FireflyAccountID, &m.AccountName)
	if err == sql.ErrNoRows {
		return nil, nil
	}
	if err != nil {
		return nil, err
	}
	return &m, nil
}
