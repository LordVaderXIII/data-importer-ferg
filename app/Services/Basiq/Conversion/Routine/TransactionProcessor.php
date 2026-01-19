<?php

declare(strict_types=1);

namespace App\Services\Basiq\Conversion\Routine;

use App\Models\ImportJob;
use App\Services\Basiq\BasiqService;
use App\Services\Basiq\ConnectionManager;
use Illuminate\Support\Facades\Log;

class TransactionProcessor
{
    private ImportJob $importJob;
    private BasiqService $service;
    private ConnectionManager $connectionManager;
    private array $existingServiceAccounts = [];

    public function __construct()
    {
        $this->service = new BasiqService();
        $this->connectionManager = new ConnectionManager($this->service);
    }

    public function setImportJob(ImportJob $importJob): void
    {
        $this->importJob = $importJob;
    }

    public function setExistingServiceAccounts(array $accounts): void
    {
        $this->existingServiceAccounts = $accounts;
    }

    public function download(): array
    {
        Log::debug('Basiq TransactionProcessor: Downloading transactions.');
        $userId = $this->connectionManager->getBasiqUserId();

        if (!$userId) {
            throw new \Exception('No Basiq user ID found for this session.');
        }

        $rawTransactions = $this->service->getTransactions($userId);
        Log::debug(sprintf('Fetched %d transactions from Basiq API.', count($rawTransactions)));

        // Organize by account ID
        $transactionsByAccount = [];
        foreach ($rawTransactions as $txn) {
            $accountId = $this->extractId($txn['account']);

            if (!isset($transactionsByAccount[$accountId])) {
                $transactionsByAccount[$accountId] = [];
            }
            $transactionsByAccount[$accountId][] = $txn;
        }

        return $transactionsByAccount;
    }

    private function extractId($accountField): string
    {
        if (is_array($accountField)) {
            return $accountField['id'] ?? '';
        }
        if (is_string($accountField)) {
            // Check if URL
            if (strpos($accountField, '/') !== false) {
                $parts = explode('/', $accountField);
                return end($parts);
            }
            return $accountField;
        }
        return '';
    }

    public function getImportJob(): ImportJob
    {
        return $this->importJob;
    }
}
