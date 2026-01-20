package server

import (
	"fmt"
	"log"
	"math"
	"strconv"
	"time"
	"fidi/internal/basiq"
	"fidi/internal/firefly"
	"fidi/internal/storage"
)

// SyncManager handles the synchronization logic
type SyncManager struct {
	Basiq   *basiq.Client
	Firefly *firefly.Client
	Storage *storage.DB // Assume this is available or passed
}

// PerformSync runs the synchronization process
// This function needs to be part of the Server struct or accessible
func (s *Server) PerformSync() error {
	log.Println("Starting synchronization...")

	// 1. Get Basiq User ID
	userID, err := s.db.GetKV("basiq_user_id")
	if err != nil {
		return fmt.Errorf("failed to get user id: %w", err)
	}
	if userID == "" {
		return fmt.Errorf("no basiq user connected")
	}

	// 2. Get Mappings
	mappings, err := s.db.GetMappings()
	if err != nil {
		return fmt.Errorf("failed to get mappings: %w", err)
	}
	if len(mappings) == 0 {
		return fmt.Errorf("no accounts mapped")
	}

	// 3. Initialize Clients
	// Re-init with latest keys if needed, but assuming config is static for now
	// Or maybe keys are in DB? Plan said Env vars for keys.
	// We use s.cfg
	bClient := basiq.New(s.cfg.BasiqAPIKey)
	fClient := firefly.New(s.cfg.FireflyURL, s.cfg.FireflyAccessToken)

	totalImported := 0

	// 4. Iterate Mappings
	for _, m := range mappings {
		log.Printf("Syncing account %s -> %s", m.BasiqAccountID, m.FireflyAccountID)

		// Get last sync date for this account? Or global?
		// Global for simplicity or per account.
		// Let's rely on Firefly duplicate detection or use a short window (e.g. 7 days) if "since" is not stored.
		// Ideally we store "last_sync_<basiq_account_id>".
		lastSyncKey := "last_sync_" + m.BasiqAccountID
		lastSyncVal, _ := s.db.GetKV(lastSyncKey)

		var since string
		if lastSyncVal != "" {
			since = lastSyncVal
		} else {
			// Default to 30 days ago
			since = time.Now().AddDate(0, 0, -30).Format("2006-01-02")
		}

		txs, err := bClient.GetTransactions(userID, m.BasiqAccountID, since)
		if err != nil {
			log.Printf("Error fetching transactions for %s: %v", m.BasiqAccountID, err)
			continue
		}

		count := 0
		newestDate := since

		for _, tx := range txs {
			// Convert Basiq Tx to Firefly Tx
			amount, _ := strconv.ParseFloat(tx.Amount, 64)
			// Basiq amount is negative for debit?
			// Usually: Debit is negative, Credit is positive.
			// Firefly: Withdrawal needs positive amount but type=withdrawal. Deposit needs positive amount type=deposit.

			ffTx := firefly.Transaction{
				Description: tx.Description,
				Date:        tx.PostDate, // ISO 8601
				ExternalID:  tx.ID,
			}

			if amount < 0 {
				ffTx.Type = "withdrawal"
				ffTx.Amount = fmt.Sprintf("%.2f", math.Abs(amount))
				ffTx.SourceID = m.FireflyAccountID
			} else {
				ffTx.Type = "deposit"
				ffTx.Amount = fmt.Sprintf("%.2f", amount)
				ffTx.DestinationID = m.FireflyAccountID
			}

			if err := fClient.CreateTransaction(ffTx); err != nil {
				log.Printf("Failed to import transaction %s: %v", tx.ID, err)
			} else {
				count++
			}

			// Update newestDate if tx.PostDate > newestDate
			if tx.PostDate > newestDate {
				newestDate = tx.PostDate
			}
		}

		log.Printf("Imported %d transactions for account %s", count, m.BasiqAccountID)
		totalImported += count

		// Update last sync
		s.db.SetKV(lastSyncKey, newestDate)
	}

	// Update global last run
	s.db.SetKV("last_run", time.Now().Format(time.RFC3339))
	s.db.SetKV("last_run_status", fmt.Sprintf("Success: %d transactions", totalImported))

	return nil
}

func (s *Server) StartScheduler() {
	ticker := time.NewTicker(24 * time.Hour)
	go func() {
		for range ticker.C {
			log.Println("Running scheduled sync...")
			if err := s.PerformSync(); err != nil {
				log.Printf("Scheduled sync failed: %v", err)
				s.db.SetKV("last_run_status", fmt.Sprintf("Failed: %v", err))
			}
		}
	}()
}
