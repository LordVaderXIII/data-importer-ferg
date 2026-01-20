package config

import (
	"os"
)

type Config struct {
	DatabasePath        string
	BasiqAPIKey         string
	FireflyURL          string
	FireflyAccessToken  string
}

func Load() (*Config, error) {
	// Defaults
	dbPath := "database/database.sqlite"
	if os.Getenv("DB_PATH") != "" {
		dbPath = os.Getenv("DB_PATH")
	}

	return &Config{
		DatabasePath:       dbPath,
		BasiqAPIKey:        os.Getenv("BASIQ_API_KEY"),
		FireflyURL:         os.Getenv("FIREFLY_III_URL"),
		FireflyAccessToken: os.Getenv("FIREFLY_III_ACCESS_TOKEN"),
	}, nil
}
