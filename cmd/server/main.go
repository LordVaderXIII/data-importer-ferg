package main

import (
	"log"
	"net/http"
	"os"

	"fidi/internal/config"
	"fidi/internal/server"
	"fidi/internal/storage"
)

func main() {
	// 1. Load Configuration
	cfg, err := config.Load()
	if err != nil {
		log.Fatalf("Failed to load configuration: %v", err)
	}

	// 2. Initialize Database
	db, err := storage.New(cfg.DatabasePath)
	if err != nil {
		log.Fatalf("Failed to initialize database: %v", err)
	}
	defer db.Close()

	// 3. Initialize Server
	srv := server.New(cfg, db)

	// 4. Start Server
	port := os.Getenv("PORT")
	if port == "" {
		port = "80"
	}
	log.Printf("Starting server on port %s", port)
	if err := http.ListenAndServe(":"+port, srv); err != nil {
		log.Fatalf("Server failed: %v", err)
	}
}
