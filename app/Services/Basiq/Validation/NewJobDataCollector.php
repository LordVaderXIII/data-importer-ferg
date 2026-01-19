<?php

declare(strict_types=1);

namespace App\Services\Basiq\Validation;

use App\Models\ImportJob;
use App\Repository\ImportJob\ImportJobRepository;
use App\Services\Basiq\BasiqService;
use App\Services\Basiq\ConnectionManager;
use App\Services\Shared\Validation\NewJobDataCollectorInterface;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Facades\Log;

class NewJobDataCollector implements NewJobDataCollectorInterface
{
    private ImportJob $importJob;
    private ImportJobRepository $repository;
    private ConnectionManager $connectionManager;
    private BasiqService $service;

    public function __construct()
    {
        $this->repository = new ImportJobRepository();
        $this->service = new BasiqService();
        $this->connectionManager = new ConnectionManager($this->service);
    }

    public function collectAccounts(): MessageBag
    {
        Log::debug('BasiqNewJobDataCollector: Collecting accounts');
        $messageBag = new MessageBag();

        $userId = $this->connectionManager->getBasiqUserId();

        if (!$userId) {
            Log::debug('BasiqNewJobDataCollector: No user ID found');
            $messageBag->add('missing_user', 'No Basiq user found. Please connect your account.');
            return $messageBag;
        }

        try {
            $accounts = $this->service->getAccounts($userId);

            // Format accounts for the ImportJob
            $serviceAccounts = [];
            foreach ($accounts as $acc) {
                // Basiq Account structure: id, type, accountNo, name, currency, balance, etc.
                // We map this to a structure the frontend understands for mapping.
                $serviceAccounts[] = [
                    'id' => $acc['id'],
                    'name' => $acc['name'] ?? $acc['accountNo'] ?? 'Unknown Account',
                    'currency' => $acc['currency'] ?? 'AUD',
                    'balance' => $acc['balance'] ?? 0,
                    'type' => $acc['class']['type'] ?? 'asset', // Basiq "class.type"
                    'identifier' => $acc['accountNo'] ?? $acc['id'],
                ];
            }

            $this->importJob->setServiceAccounts($serviceAccounts);
            $this->repository->saveToDisk($this->importJob);

        } catch (\Exception $e) {
            Log::error('BasiqNewJobDataCollector: Failed to collect accounts: ' . $e->getMessage());
            $messageBag->add('api_error', $e->getMessage());
        }

        return $messageBag;
    }

    public function getFlowName(): string
    {
        return 'basiq';
    }

    public function validate(): MessageBag
    {
        return new MessageBag();
    }

    public function getImportJob(): ImportJob
    {
        return $this->importJob;
    }

    public function setImportJob(ImportJob $importJob): void
    {
        $this->importJob = $importJob;
    }
}
