<?php

declare(strict_types=1);

namespace App\Http\Controllers\Import\Basiq;

use App\Http\Controllers\Controller;
use App\Repository\ImportJob\ImportJobRepository;
use App\Services\Basiq\BasiqService;
use App\Services\Basiq\ConnectionManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

class ConnectionController extends Controller
{
    private ImportJobRepository $repository;
    private ConnectionManager $connectionManager;
    private BasiqService $service;

    public function __construct(ImportJobRepository $repository, ConnectionManager $connectionManager, BasiqService $service)
    {
        $this->repository = $repository;
        $this->connectionManager = $connectionManager;
        $this->service = $service;
    }

    public function index(string $identifier): View|RedirectResponse
    {
        $importJob = $this->repository->find($identifier);

        $userId = $this->connectionManager->getBasiqUserId();

        $connections = [];
        if ($userId) {
            try {
                $accounts = $this->service->getAccounts($userId);
                $connections = $accounts;
            } catch (\Exception $e) {
                Log::warning('Failed to fetch accounts for existing user: ' . $e->getMessage());
            }
        }

        return view('import.basiq.connect', compact('importJob', 'userId', 'connections'));
    }

    public function connect(Request $request, string $identifier): RedirectResponse
    {
        $userId = $this->connectionManager->getBasiqUserId();

        if (!$userId) {
            try {
                $userId = $this->connectionManager->createBasiqUser();
            } catch (\Exception $e) {
                return redirect()->back()->withErrors(['error' => 'Failed to create Basiq user: ' . $e->getMessage()]);
            }
        }

        try {
            $mobile = $request->input('mobile');
            $authLink = $this->service->createAuthLink($userId, $mobile);
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'Failed to generate Auth Link: ' . $e->getMessage()]);
        }

        return redirect()->away($authLink);
    }

    public function callback(string $identifier): RedirectResponse
    {
        return redirect()->route('configure-import.index', ['identifier' => $identifier]);
    }
}
