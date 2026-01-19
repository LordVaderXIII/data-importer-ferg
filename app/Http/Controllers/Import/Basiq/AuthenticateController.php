<?php

declare(strict_types=1);

namespace App\Http\Controllers\Import\Basiq;

use App\Http\Controllers\Controller;
use App\Repository\ImportJob\ImportJobRepository;
use App\Services\Basiq\Authentication\SecretManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuthenticateController extends Controller
{
    private ImportJobRepository $repository;

    public function __construct(ImportJobRepository $repository)
    {
        $this->repository = $repository;
    }

    public function index(string $identifier): View|RedirectResponse
    {
        $importJob = $this->repository->find($identifier);

        // If we already have an API Key (from env or session), check if we can skip
        if (SecretManager::getApiKey() !== '') {
            // Validate? Or just proceed to connection check
            return redirect()->route('basiq-connect.index', ['identifier' => $identifier]);
        }

        return view('import.basiq.authenticate', compact('importJob'));
    }

    public function postIndex(Request $request, string $identifier): RedirectResponse
    {
        $request->validate([
            'api_key' => 'required|string',
        ]);

        SecretManager::saveApiKey($request->input('api_key'));

        return redirect()->route('basiq-connect.index', ['identifier' => $identifier]);
    }
}
