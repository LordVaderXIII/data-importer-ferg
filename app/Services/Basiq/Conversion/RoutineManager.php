<?php

declare(strict_types=1);

namespace App\Services\Basiq\Conversion;

use App\Exceptions\ImporterErrorException;
use App\Models\ImportJob;
use App\Repository\ImportJob\ImportJobRepository;
use App\Services\Basiq\Conversion\Routine\GenerateTransactions;
use App\Services\Basiq\Conversion\Routine\TransactionProcessor;
use App\Services\Shared\Configuration\Configuration;
use App\Services\Shared\Conversion\CreatesAccounts;
use App\Services\Shared\Conversion\RoutineManagerInterface;
use Illuminate\Support\Facades\Log;
use Override;

class RoutineManager implements RoutineManagerInterface
{
    use CreatesAccounts;

    private Configuration $configuration;
    private GenerateTransactions $transactionGenerator;
    private TransactionProcessor $transactionProcessor;
    private ImportJobRepository $repository;
    private ImportJob $importJob;

    private array $downloaded;

    public function __construct(ImportJob $importJob)
    {
        $this->downloaded = [];
        $this->transactionProcessor = new TransactionProcessor();
        $this->transactionGenerator = new GenerateTransactions();
        $this->repository = new ImportJobRepository();
        $this->importJob = $importJob;
        $this->importJob->refreshInstanceIdentifier();
        $this->setConfiguration($this->importJob->getConfiguration());
    }

    #[Override]
    public function getServiceAccounts(): array
    {
        return $this->importJob->getServiceAccounts();
    }

    private function setConfiguration(Configuration $configuration): void
    {
        $this->configuration = $configuration;
        $this->transactionProcessor->setImportJob($this->importJob);
        $this->transactionGenerator->setImportJob($this->importJob);
    }

    public function start(): array
    {
        Log::debug('Starting Basiq conversion routine.');

        $this->transactionProcessor->setExistingServiceAccounts($this->getServiceAccounts());

        // 1. Download transactions from Basiq
        try {
            $this->downloaded = $this->transactionProcessor->download();
        } catch (\Exception $e) {
            Log::error('Basiq download failed: ' . $e->getMessage());
            $this->importJob->conversionStatus->addError(0, 'Failed to download transactions from Basiq: ' . $e->getMessage());
            $this->repository->saveToDisk($this->importJob);
            throw $e;
        }

        if (empty($this->downloaded)) {
            Log::warning('No transactions downloaded from Basiq.');
            return [];
        }

        // 2. Collect target accounts from Firefly III
        $this->transactionGenerator->collectTargetAccounts();

        // 3. Generate Firefly III transactions
        $transactions = $this->transactionGenerator->getTransactions($this->downloaded);
        Log::debug(sprintf('Generated %d Firefly III transactions from Basiq data.', count($transactions)));

        return $transactions;
    }

    public function getImportJob(): ImportJob
    {
        return $this->importJob;
    }
}
