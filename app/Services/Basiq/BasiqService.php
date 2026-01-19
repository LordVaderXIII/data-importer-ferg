<?php

declare(strict_types=1);

namespace App\Services\Basiq;

use App\Services\Basiq\TokenManager;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BasiqService
{
    private const BASE_URL = 'https://au-api.basiq.io';

    public function createUser(string $email = null, string $mobile = null): array
    {
        $token = TokenManager::getAccessToken();

        $body = [];
        if ($email) {
            $body['email'] = $email;
        }
        if ($mobile) {
            $body['mobile'] = $mobile;
        }

        $response = Http::withToken($token)
            ->withHeaders(['basiq-version' => '3.0'])
            ->post(self::BASE_URL . '/users', $body);

        if ($response->failed()) {
            Log::error('Failed to create Basiq user', ['body' => $response->body()]);
            throw new \Exception('Failed to create Basiq user.');
        }

        return $response->json();
    }

    public function getUser(string $userId): array
    {
        $token = TokenManager::getAccessToken();

        $response = Http::withToken($token)
            ->withHeaders(['basiq-version' => '3.0'])
            ->get(self::BASE_URL . '/users/' . $userId);

        if ($response->failed()) {
            Log::error('Failed to get Basiq user', ['userId' => $userId, 'body' => $response->body()]);
            throw new \Exception('Failed to get Basiq user.');
        }

        return $response->json();
    }

    public function createAuthLink(string $userId, ?string $mobile = null): string
    {
        $token = TokenManager::getAccessToken();

        $body = [];
        if ($mobile) {
            $body['mobile'] = $mobile;
        }

        $response = Http::withToken($token)
            ->withHeaders(['basiq-version' => '3.0'])
            ->post(self::BASE_URL . '/users/' . $userId . '/auth_link', $body);

        if ($response->failed()) {
            Log::error('Failed to create auth link', ['body' => $response->body()]);
            throw new \Exception('Failed to create auth link.');
        }

        return $response->json()['links']['public'];
    }

    public function getAccounts(string $userId): array
    {
        $token = TokenManager::getAccessToken();

        $response = Http::withToken($token)
            ->withHeaders(['basiq-version' => '3.0'])
            ->get(self::BASE_URL . '/users/' . $userId . '/accounts');

        if ($response->failed()) {
            Log::error('Failed to get accounts', ['body' => $response->body()]);
            throw new \Exception('Failed to get accounts.');
        }

        return $response->json()['data'];
    }

    public function getTransactions(string $userId): array
    {
        $token = TokenManager::getAccessToken();

        $response = Http::withToken($token)
            ->withHeaders(['basiq-version' => '3.0'])
            ->get(self::BASE_URL . '/users/' . $userId . '/transactions');

        if ($response->failed()) {
            Log::error('Failed to get transactions', ['body' => $response->body()]);
            throw new \Exception('Failed to get transactions.');
        }

        return $response->json()['data'];
    }
}
