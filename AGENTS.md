# AGENTS.md

This file describes the tools and agents in this repository to help Jules and other agents work effectively.

## Repository Overview

This repository is a clone of the Firefly III Data Importer (FIDI). It is a Laravel-based application designed to import financial data into [Firefly III](https://github.com/firefly-iii/firefly-iii).

## Key Components

*   **Import Flows:** The application supports various "flows" for importing data (e.g., CSV, Nordigen, Spectre, PayPal, etc.). These are located in `app/Services/{FlowName}`.
*   **Job Management:** Imports are managed as "Jobs". `app/Models/ImportJob` and `app/Repository/ImportJob/ImportJobRepository.php` handle the state and persistence of these jobs.
*   **Controllers:** `app/Http/Controllers/Import` contains the controllers for the multi-step import wizard.
*   **Configuration:** `app/Services/Shared/Configuration/Configuration.php` manages the import configuration.

## Development Guidelines

### Adding a New Integration (e.g., Basiq)

1.  **Service Layer:** Create a new directory in `app/Services/` (e.g., `app/Services/Basiq`).
    *   Implement authentication and token management.
    *   Implement data retrieval (accounts, transactions).
    *   Implement a `NewJobDataCollector` if necessary to gather initial data.
2.  **Routes:** Add routes in `routes/web.php` for the authentication flow (e.g., redirecting to the provider, handling callbacks).
3.  **Controllers:** Create controllers in `app/Http/Controllers/Import/Basiq` to handle the specific steps.
4.  **Integration:**
    *   Update `app/Repository/ImportJob/ImportJobRepository.php` to handle the new flow in `parseImportJob`.
    *   Update `app/Http/Controllers/Import/UploadController.php` or `AuthenticateController.php` to initiate the flow.

### Testing

*   Run tests using `php artisan test`.
*   Ensure environment variables are set correctly for integration tests.

### Code Style

*   Follow PSR-12 coding standards.
*   Strict typing is encouraged (`declare(strict_types=1);`).

### Basiq Integration Specifics

*   **API Key:** Can be provided via UI or `.env` (`BASIQ_API_KEY`).
*   **User Persistence:** Basiq `userId` should be persisted to avoid re-linking accounts. This is stored in a local SQLite database.
*   **Mappings:** Account mappings should be persistent.
*   **Duplication:** Rely on Firefly III's built-in transaction duplicate detection.

## Tools & Utilities

*   `artisan`: Laravel's command-line interface. Use it for migrations, serving the app, etc.
*   `composer`: Dependency manager.
