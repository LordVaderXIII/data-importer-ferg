# FIDI - Firefly III Data Importer (Basiq Edition)

This repository contains a specialized data importer for Firefly III, integrated with the Basiq API for Australian banks. It has been rewritten in Go for performance, simplicity, and ease of deployment.

## Architecture

*   **Language:** Go (Golang) 1.24+
*   **Database:** SQLite (Embedded via `modernc.org/sqlite`, CGO-free)
*   **Web Framework:** Standard `net/http` with `html/template`.
*   **Frontend:** HTMX + TailwindCSS (CDN).
*   **Deployment:** Docker (Alpine based).

## Directory Structure

*   `cmd/server`: Application entry point.
*   `internal/basiq`: Basiq API client (Auth, Accounts, Transactions).
*   `internal/firefly`: Firefly III API client.
*   `internal/server`: HTTP handlers and synchronization logic.
*   `internal/storage`: SQLite database wrapper (KV store & Mappings).
*   `web/templates`: HTML templates.
*   `web/static`: Static assets.

## Setup & Configuration

The application requires the following environment variables:

*   `BASIQ_API_KEY`: Your Basiq API key.
*   `FIREFLY_III_URL`: URL to your Firefly III instance (e.g., `http://192.168.1.10:8080`).
*   `FIREFLY_III_ACCESS_TOKEN`: Personal Access Token from Firefly III.
*   `DB_PATH`: Path to SQLite database (default: `database/database.sqlite`).

### Docker

The Docker image uses a multi-stage build to produce a small Alpine-based image.

```bash
docker build -t fidi .
docker run -d \
  -p 80:80 \
  -v $(pwd)/database:/app/database \
  -e BASIQ_API_KEY=your_key \
  -e FIREFLY_III_URL=http://your_firefly \
  -e FIREFLY_III_ACCESS_TOKEN=your_token \
  fidi
```

## Agents & Development

This file (`AGENTS.md`) serves as a guide for AI agents and developers.

*   **Go Code:** Run `go build -o fidi ./cmd/server` to build.
*   **Tests:** Run `go test ./...` to run all tests.
*   **Database:** The application automatically handles schema migrations on startup. Legacy tables from previous PHP versions are wiped if detected.
*   **Frontend:** Edit templates in `web/templates`. No build step required (Tailwind is loaded via CDN for simplicity, or can be added to static).

## Usage

1.  **Dashboard:** Access the web UI at `http://localhost`.
2.  **Connect:** Enter your email/mobile to create a Basiq User and link your bank.
3.  **Map:** Map your Basiq accounts to Firefly III accounts.
4.  **Sync:** Click "Sync Now" to import transactions immediately. A daily schedule runs automatically.
