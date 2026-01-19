<?php

declare(strict_types=1);

namespace App\Services\Basiq\Conversion\Routine;

use App\Models\ImportJob;
use App\Services\Shared\Conversion\CollectsAccounts;
use App\Services\Shared\Conversion\GeneratesTransactions;
use Illuminate\Support\Facades\Log;

class GenerateTransactions
{
    use CollectsAccounts;
    use GeneratesTransactions;

    private ImportJob $importJob;

    public function setImportJob(ImportJob $importJob): void
    {
        $this->importJob = $importJob;
    }

    public function getTransactions(array $downloaded): array
    {
        Log::debug('Basiq GenerateTransactions: Generating Firefly III transactions.');
        $result = [];

        foreach ($downloaded as $accountId => $transactions) {
            // Find the Firefly III account mapped to this Basiq account
            $fireflyAccount = $this->findFireflyAccount($accountId);

            if (!$fireflyAccount) {
                Log::warning(sprintf('Skipping transactions for Basiq account %s (Not mapped).', $accountId));
                continue;
            }

            foreach ($transactions as $txn) {
                $converted = $this->convertTransaction($txn, $fireflyAccount);
                if ($converted) {
                    $result[] = $converted;
                }
            }
        }

        return $result;
    }

    private function findFireflyAccount(string $basiqAccountId): ?array
    {
        // $mapping is [ 'BasiqAccountID' => 'FireflyAccountID' ] (if mapped)
        $mapping = $this->importJob->getConfiguration()->getAccounts();

        if (isset($mapping[$basiqAccountId])) {
            $fireflyId = (int)$mapping[$basiqAccountId];
            // $this->myAccounts is populated by CollectsAccounts with Firefly account details
            return $this->myAccounts[$fireflyId] ?? null;
        }

        return null;
    }

    private function convertTransaction(array $txn, array $fireflyAccount): ?array
    {
        // Basiq Transaction Structure mapping to Firefly III
        $amount = $txn['amount'] ?? 0;
        $description = $txn['description'] ?? 'Unknown Transaction';
        $date = $txn['transactionDate'] ?? $txn['postDate'] ?? date('Y-m-d');
        $id = $txn['id'];
        $direction = $txn['direction'] ?? 'debit';

        $finalAmount = (float)$amount;
        if ($direction === 'debit') {
            $finalAmount = -$finalAmount;
        }

        return [
            'date' => date('Y-m-d', strtotime($date)),
            'description' => $description,
            'amount' => abs($finalAmount),
            'currency_code' => $txn['currency'] ?? 'AUD',
            'foreign_amount' => null,
            'foreign_currency_code' => null,
            'budget_id' => null,
            'category_name' => $txn['class']['code'] ?? null,
            'source_name' => $direction === 'debit' ? $fireflyAccount['name'] : null,
            'source_iban' => null,
            'destination_name' => $direction === 'credit' ? $fireflyAccount['name'] : null,
            'destination_iban' => null,
            'type' => $direction === 'credit' ? 'deposit' : 'withdrawal',
            'notes' => 'Imported from Basiq via FIDI',
            'external_id' => $id,
            'internal_reference' => $id,
        ];
    }
}
